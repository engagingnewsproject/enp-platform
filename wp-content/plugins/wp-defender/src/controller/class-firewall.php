<?php
/**
 * Handles IP lockouts, notifications, and settings related to the firewall features.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Exception;
use WP_Defender\Event;
use Calotes\Helper\HTTP;
use WP_Defender\Traits\IP;
use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\Array_Cache;
use WP_Defender\Controller\Dashboard;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Model\Setting\Antibot_Global_Firewall_Setting;
use WP_Defender\Component\Mail;
use WP_Defender\Traits\Formats;
use WP_Defender\Model\Unlockout;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Lockout_Ip;
use WP_Defender\Model\Lockout_Log;
use WP_Defender\Component\Unlock_Me;
use WP_Defender\Component\IP\Antibot_Global_Firewall as Antibot_Global_Firewall_Component;
use WP_Defender\Component\IP\Global_IP as Global_IP_Component;
use WP_Defender\Component\Blacklist_Lockout;
use WP_Defender\Component\Http\Remote_Address;
use MaxMind\Db\Reader\InvalidDatabaseException;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\Global_Ip_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Component\Firewall as Firewall_Service;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Setting\Firewall as Firewall_Settings;
use WP_Defender\Component\User_Agent as User_Agent_Component;
use WP_Defender\Model\Setting\Blacklist_Lockout as Blacklist_Model;
use WP_Defender\Model\Setting\Login_Lockout as Login_Lockout_Model;
use WP_Defender\Component\Trusted_Proxy_Preset\Trusted_Proxy_Preset;
use WP_Defender\Component\Smart_Ip_Detection;
use WP_Defender\Helper\Analytics\Firewall as Firewall_Analytics;
use WP_Defender\Model\Antibot_Global_Firewall as Antibot_Global_Firewall_Model;
use WP_Defender\Component\Altcha_Handler;
use WP_Defender\Controller\Hub_Connector;
use WP_Defender\Integrations\Main_Wp;
use WP_Defender\Component\Breadcrumbs;

/**
 * Handles IP lockouts, notifications, and settings related to the firewall features.
 */
class Firewall extends Event {

	use IP;
	use Formats;

	public const FIREWALL_LOG = 'firewall.log';
	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * The model for handling the data.
	 *
	 * @var Firewall_Settings
	 */
	protected $model;

	/**
	 * Service for handling logic.
	 *
	 * @var Firewall_Service
	 */
	public $service;

	/**
	 * Service for handling Smart IP Detection.
	 *
	 * @var Smart_Ip_Detection
	 */
	public $service_sid;

	/**
	 * The WPMUDEV instance used for interacting with WPMUDEV services.
	 *
	 * @var WPMUDEV
	 */
	private $wpmudev;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->wpmudev = wd_di()->get( WPMUDEV::class );

		$title = esc_html__( 'Firewall', 'wpdef' );
		$this->register_page(
			$this->get_title( $title ),
			$this->slug,
			array( $this, 'main_view' ),
			$this->parent_slug,
			null,
			$this->menu_title( $title )
		);
		$this->model       = wd_di()->get( Firewall_Settings::class );
		$this->service     = wd_di()->get( Firewall_Service::class );
		$this->service_sid = wd_di()->get( Smart_Ip_Detection::class );
		$this->register_routes();
		$this->maybe_show_demo_lockout();
		$this->maybe_lockout_gathered_ips();
		// Todo: pass $ip as argument to Login_Lockout/Nf_Lockout.
		wd_di()->get( Login_Lockout::class );
		wd_di()->get( Nf_Lockout::class );
		wd_di()->get( Blacklist::class );
		wd_di()->get( Firewall_Logs::class );
		wd_di()->get( UA_Lockout::class );
		wd_di()->get( Global_Ip::class );
		wd_di()->get( Antibot_Global_Firewall::class );
		wd_di()->get( Malicious_Bot::class );
		wd_di()->get( Fake_Bot_Detection::class );

		// Integrate MainWP plugin.
		wd_di()->get( Main_Wp::class );

		/**
		 * Network Cron Manager
		 *
		 * @var Network_Cron_Manager $network_cron_manager
		 */
		$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
		$network_cron_manager->register_callback(
			'firewall_clean_up_logs',
			array( $this->service, 'firewall_clean_up_logs' ),
			HOUR_IN_SECONDS,
			time() + 10
		);
		$network_cron_manager->register_callback(
			'wpdef_firewall_clean_up_lockout',
			array( $this->service, 'firewall_clean_up_lockout' ),
			WEEK_IN_SECONDS,
			time() + 10
		);
		$network_cron_manager->register_callback(
			'wpdef_firewall_clean_up_unlockout',
			array( $this, 'clean_up_unlockout' ),
			WEEK_IN_SECONDS,
			time() + 20
		);
		$network_cron_manager->register_callback(
			'wpdef_firewall_fetch_trusted_proxy_preset_ips',
			array( $this->service, 'update_trusted_proxy_preset_ips' ),
			DAY_IN_SECONDS
		);
		if ( $this->service_sid->is_smart_ip_detection_enabled() ) {
			$network_cron_manager->register_callback(
				'wpdef_smart_ip_detection_ping',
				array( $this->service_sid, 'smart_ip_detection_ping' ),
				WEEK_IN_SECONDS
			);
		}
		$network_cron_manager->register_callback(
			'wpdef_firewall_whitelist_server_public_ip',
			array( $this->service, 'set_whitelist_server_public_ip' ),
			12 * HOUR_IN_SECONDS,
			time() + 15
		);
		// Additional hooks.
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ), 11 );
		add_action( 'admin_print_scripts', array( $this, 'print_emoji_script' ) );

		$this->maybe_extend_mime_types();
		// Schedule cleanup blocklist ips event.
		$this->schedule_cleanup_blocklist_ips_event();
		add_action( 'wp_ajax_' . Smart_Ip_Detection::ACTION_PING, array( $this, 'handle_detect_ip_header' ) );
		add_action( 'wp_ajax_nopriv_' . Smart_Ip_Detection::ACTION_PING, array( $this, 'handle_detect_ip_header' ) );

		add_action( 'admin_init', array( $this, 'mark_page_visited' ) );
	}

	/**
	 * Return the title of the page.
	 *
	 * @param string $default_text The original menu title.
	 *
	 * @return string
	 */
	public function get_title( $default_text ): string {
		return $default_text;
	}

	/**
	 * Get menu title.
	 *
	 * @param string $default_text The original menu title.
	 *
	 * @return string
	 */
	protected function menu_title( string $default_text ): string {
		// Breadcrumbs are only for Pro features.
		if ( ! $this->wpmudev->is_pro() ) {
			return $default_text;
		}
		// Check if the user has already visited the feature page.
		if ( wd_di()->get( Breadcrumbs::class )->get_meta_key() ) {
			return $default_text;
		}

		return $default_text . '<span class="wd-new-feature-dot"></span>';
	}

	/**
	 * This is for handling request from dashboard.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function dashboard_activation() {
		$il = wd_di()->get( Login_Lockout_Model::class );
		$nf = wd_di()->get( Notfound_Lockout::class );
		$ua = wd_di()->get( User_Agent_Lockout::class );

		$il->enabled = true;
		$il->save();
		$nf->enabled = true;
		$nf->save();
		$ua->enabled = true;
		$ua->save();

		return new Response( true, $this->to_array() );
	}

	/**
	 * Render the view page.
	 *
	 * @return void
	 */
	public function main_view(): void {
		$this->render( 'main' );
	}

	/**
	 * Save settings.
	 *
	 * @param  Request $request  The request object containing new settings data.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function save_settings( Request $request ): Response {
		$data = $request->get_data_by_model( $this->model );
		// Before updating Trusted Proxy Preset (TPP) IP's, check the current option is a custom header, no blank TPP value and there's TPP change.
		$is_preset_update = false;
		if (
			in_array(
				$data['http_ip_header'],
				Firewall_Service::custom_http_headers(),
				true
			)
			&& ! empty( $data['trusted_proxy_preset'] )
			&& $data['trusted_proxy_preset'] !== $this->model->trusted_proxy_preset
		) {
			$is_preset_update = true;
		}

		$is_ip_detection_type_changed = false;
		if ( 'automatic' === $data['ip_detection_type'] && $this->model->ip_detection_type !== $data['ip_detection_type'] ) {
			$is_ip_detection_type_changed = true;
		}

		$is_http_ip_header_changed = false;
		if ( $this->model->http_ip_header !== $data['http_ip_header'] ) {
			$is_http_ip_header_changed = true;
		}

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->service->update_cron_schedule_interval( $data['ip_blocklist_cleanup_interval'] );
			$this->model->save();
			Config_Hub_Helper::set_clear_active_flag();
			// Fetch trusted proxy ips.
			if ( $is_preset_update ) {
				$this->service->update_trusted_proxy_preset_ips();
			}

			if ( $is_ip_detection_type_changed ) {
				$this->service_sid->smart_ip_detection_ping();
			}
			// Maybe track.
			if ( ( $is_ip_detection_type_changed || $is_http_ip_header_changed )
				&& ! defender_is_wp_cli()
			) {
				$firewall_analytics = wd_di()->get( Firewall_Analytics::class );
				$detection_method   = Firewall_Analytics::get_detection_method_label(
					$data['ip_detection_type'],
					$data['http_ip_header']
				);

				$firewall_analytics->track_feature(
					Firewall_Analytics::EVENT_IP_DETECTION,
					array( Firewall_Analytics::PROP_IP_DETECTION => $detection_method )
				);
			}

			return new Response(
				true,
				array(
					'message'    => esc_html__( 'Your settings have been updated.', 'wpdef' ),
					'auto_close' => true,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $this->model->get_formatted_errors(),
			)
		);
	}

	/**
	 * Converts the current object to an array representation.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		$il = wd_di()->get( Login_Lockout_Model::class );
		$nf = wd_di()->get( Notfound_Lockout::class );
		$ua = wd_di()->get( User_Agent_Lockout::class );

		return array_merge(
			array(
				'summary'      => array(
					'ip'          => array(
						'week' => Lockout_Log::count_login_lockout_last_7_days(),
					),
					'nf'          => array(
						'week' => Lockout_Log::count_404_lockout_last_7_days(),
					),
					'ua'          => array(
						'week' => Lockout_Log::count_ua_lockout_last_7_days(),
					),
					'lastLockout' => Lockout_Log::get_last_lockout_date(),
				),
				'notification' => true,
				'enabled'      => $nf->enabled || $il->enabled || $ua->enabled,
				'enable_login' => $il->enabled,
				'enable_404'   => $nf->enabled,
				'enable_ua'    => $ua->enabled,
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		wp_enqueue_media();

		wp_localize_script( 'def-iplockout', 'iplockout', $this->data_frontend() );
		wp_enqueue_script( 'def-iplockout' );
		$this->enqueue_main_assets();

		do_action( 'defender_ip_lockout_action_assets' );
	}

	/**
	 * Renders the preview of lockout screen.
	 *
	 * @return void
	 */
	private function maybe_show_demo_lockout(): void {
		$is_test = HTTP::get( 'def-lockout-demo', 0 );
		if ( 1 === (int) $is_test ) {
			$type = HTTP::get( 'type' );

			$remaining_time = 0;

			switch ( $type ) {
				case 'login':
					$settings       = wd_di()->get( Login_Lockout_Model::class );
					$message        = $settings->lockout_message;
					$remaining_time = 3600;
					break;
				case '404':
					$settings       = wd_di()->get( Notfound_Lockout::class );
					$message        = $settings->lockout_message;
					$remaining_time = 3600;
					break;
				case 'blocklist':
					$settings = wd_di()->get( Blacklist_Model::class );
					$message  = $settings->ip_lockout_message;
					break;
				case User_Agent_Lockout::get_module_slug():
					$settings       = wd_di()->get( User_Agent_Lockout::class );
					$message        = $settings->message;
					$remaining_time = 3600;
					break;
				default:
					$message = esc_html__( 'Demo', 'wpdef' );
					break;
			}

			$this->actions_for_blocked( $message, $remaining_time, 'demo', $this->get_user_ip() );
			exit;
		}
	}

	/**
	 * Checks the attempt counter for a blocked IP address.
	 *
	 * @param  string $blocked_ip  The blocked IP address to check the attempt counter for.
	 *
	 * @return bool
	 */
	private function check_attempt_counter_by( $blocked_ip ): bool {
		$blocked_ip    = $this->check_ip_by_remote_addr( $blocked_ip );
		$request_count = get_transient( $blocked_ip );
		$disabled      = false;
		if ( false === $request_count ) {
			set_transient( $blocked_ip, 1, Unlock_Me::EXPIRED_COUNTER_TIME );
		} elseif ( (int) $request_count >= Unlock_Me::get_attempt_limit() ) {
			$disabled = true;
		} else {
			++$request_count;
			set_transient( $blocked_ip, $request_count, Unlock_Me::EXPIRED_COUNTER_TIME );
		}

		return $disabled;
	}

	/**
	 * Verify if the user is blocked.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 * @throws InvalidDatabaseException When unexpected data is found in the database.
	 */
	public function verify_blocked_user( Request $request ): Response {
		$data        = $request->get_data(
			array(
				'user_data' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$maybe_email = $data['user_data'];
		if ( empty( $maybe_email ) ) {
			return new Response( false, array() );
		}
		$ips = $this->get_user_ip();
		// Check if at least one IP is blocked.
		$blocked_ip = $this->service->get_blocked_ip( $ips );
		// If nothing, just return.
		if ( '' === $blocked_ip ) {
			return new Response( false, array() );
		}
		// Maybe is it a user email?
		$user = get_user_by( 'email', $maybe_email );
		if ( ! is_object( $user ) ) {
			// Maybe is it a username?
			$user = get_user_by( 'login', $maybe_email );
			if ( ! is_object( $user ) ) {
				$this->check_attempt_counter_by( $blocked_ip );

				return new Response( false, array() );
			}
		}
		// Send email only for admins.
		if ( ! $this->is_admin( $user ) ) {
			// No need to count attempts for existed user but non-admin.
			return new Response( false, array() );
		}
		// Create Unlockout records.
		$arr_uids = array();
		foreach ( $ips as $ip ) {
			// Collect blocked IP's.
			$created_id = wd_di()->get( Unlockout::class )->create( $ip, $user->user_email );
			if ( $created_id ) {
				$arr_uids[] = $created_id;
			}
		}

		$this->send_unlock_email( $user->user_email, $user->user_login, $arr_uids );

		return new Response( true, array() );
	}

	/**
	 * Send again if the attempt limit has not expired.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 * @throws InvalidDatabaseException When unexpected data is found in the database.
	 */
	public function send_again(): Response {
		// Check if at least one IP is blocked.
		$blocked_ip = $this->service->get_blocked_ip( $this->get_user_ip() );
		if ( '' === $blocked_ip ) {
			return new Response( false, array() );
		}
		$request_count = get_transient( $this->check_ip_by_remote_addr( $blocked_ip ) );
		$is_expired    = false !== $request_count && $request_count >= Unlock_Me::get_attempt_limit();

		return new Response(
			! $is_expired,
			array()
		);
	}

	/**
	 * Sends an unlock email to the user.
	 *
	 * @param  string $user_email  The email address of the user.
	 * @param  string $user_login  The login name of the user.
	 * @param  array  $arr_uids  The array of unique IDs.
	 *
	 * @return bool True if the email is sent successfully, false otherwise.
	 */
	protected function send_unlock_email( $user_email, $user_login, $arr_uids ): bool {
		$headers = wd_di()->get( Mail::class )->get_headers(
			defender_noreply_email( 'wd_unlock_noreply_email' ),
			Unlock_Me::SLUG_UNLOCK
		);
		$subject = esc_html__( 'Request to Unblock IP Address', 'wpdef' );

		$content_body = $this->render_partial(
			'email/unlockout',
			array(
				'subject'        => $subject,
				'name'           => $user_login,
				'unlocked_link'  => Unlock_Me::create_url( $user_email, $user_login, $arr_uids ),
				'generated_time' => $this->get_local_human_date( time() ),
			),
			false
		);
		$content      = $this->render_partial(
			'email/index',
			array(
				'title'            => esc_html__( 'Firewall', 'wpdef' ),
				'content_body'     => $content_body,
				'unsubscribe_link' => '',
			),
			false
		);

		// Send email.
		return wp_mail( $user_email, $subject, $content, $headers );
	}

	/**
	 * Run actions for locked entities.
	 *
	 * @param  string $message  The message to show.
	 * @param  int    $remaining_time  Remaining countdown time in seconds.
	 * @param  string $reason  Block's reason.
	 * @param  array  $ips  Array of blocked IP's.
	 * @param  bool   $discourage_crawlers  Whether to discourage crawlers with a noindex meta tag.
	 *
	 * @return void
	 */
	public function actions_for_blocked(
		string $message,
		int $remaining_time = 0,
		string $reason = '',
		array $ips = array(),
		bool $discourage_crawlers = false
	): void {
		$action = HTTP::get( 'action', false );

		if ( defender_base_action() === $action ) {
			$nonce = HTTP::get( '_def_nonce', false );
			$route = HTTP::get( 'route', '' );
			$route = wp_unslash( $route );
			if ( wp_verify_nonce( $nonce, $route ) ) {
				return;
			}
		}
		// Maybe unblock the request?
		if ( Unlock_Me::SLUG_UNLOCK === $action && wd_di()->get( Unlock_Me::class )->maybe_unlock() ) {
			return;
		}
		// Create a Lockout cookie to avoid caching in real case.
		if ( 'demo' !== $reason ) {
			// We follow the default naming process to find the required cookie later.
			$cookie_name = str_replace( '.', '_', $ips[0] );
			$cookie_name = 'wpdef_lockout_' . $cookie_name;
			if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
				setcookie( $cookie_name, true, time() + HOUR_IN_SECONDS, '/', '', is_ssl(), true );
			}
		}

		$global_service = wd_di()->get( Global_IP_Component::class );
		if ( Global_IP_Component::REASON_SLUG === $reason ) {
			$global_service->log_event( $ips[0] );
		}

		$antibot_service = wd_di()->get( Antibot_Global_Firewall_Component::class );
		if ( Antibot_Global_Firewall_Component::REASON_SLUG === $reason ) {
			$antibot_service->log_ip_message( 'Blocked IP(s): ' . implode( ', ', $ips ) );
		}

		ob_start();

		if ( ! headers_sent() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}

			header( 'HTTP/1.0 403 Forbidden' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' ); // HTTP 1.1.
			header( 'Pragma: no-cache' ); // HTTP 1.0.
			header( 'Expires: ' . wp_date( 'D, d M Y H:i:s', time() - 3600 ) . ' GMT' ); // Proxies.
			header( 'Clear-Site-Data: "cache"' ); // Clear cache of the current request.

			$global_ip_lockout   = wd_di()->get( Global_Ip_Lockout::class );
			$is_displayed        = Unlock_Me::is_displayed( $reason, $ips );
			$is_displayed_agf    = $antibot_service->is_displayed( $ips );
			$allow_self_unlock   = $global_ip_lockout->allow_self_unlock;
			$hide_btn_agf        = $is_displayed_agf && ! $allow_self_unlock;
			$malicious_bot       = wd_di()->get( Malicious_Bot::class );
			$discourage_crawlers = ! $discourage_crawlers && $malicious_bot->is_hash_request() ? true : $discourage_crawlers;
			$params              = array(
				'message'             => ! $hide_btn_agf ? $message : '',
				'remaining_time'      => $remaining_time,
				'is_unlock_me'        => $is_displayed,
				'is_unlock_me_agf'    => $is_displayed_agf,
				'module_name_agf'     => Antibot_Global_Firewall_Setting::get_module_name(),
				'hide_btn_agf'        => $hide_btn_agf,
				'discourage_crawlers' => $discourage_crawlers,
			);

			// For AntiBot Global Firewall "Unlock me captcha".
			if ( $is_displayed_agf && $allow_self_unlock ) {
				$altcha_challenge = wd_di()->get( Altcha_Handler::class )->create_challenge();
				$collection       = $this->dump_routes_and_nonces();
				$routes           = $collection['routes'];
				$nonces           = $collection['nonces'];
				$args             = array(
					'action'     => defender_base_action(),
					'_def_nonce' => $nonces['agf_unlock_user'],
					'route'      => $this->check_route( $routes['agf_unlock_user'] ),
				);

				$params['action_agf_unlock_user'] = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
				$params['button_title']           = Antibot_Global_Firewall_Component::get_button_text();
				$params['altcha']                 = $altcha_challenge;
			} elseif ( $is_displayed ) { // Only for "Unlock me".
				$collection = $this->dump_routes_and_nonces();
				$routes     = $collection['routes'];
				$nonces     = $collection['nonces'];
				// Prepare data.
				$args                                 = array(
					'action'     => defender_base_action(),
					'_def_nonce' => $nonces['verify_blocked_user'],
					'route'      => $this->check_route( $routes['verify_blocked_user'] ),
				);
				$params['action_verify_blocked_user'] = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
				// Rewrite args for another action.
				$args['_def_nonce']          = $nonces['send_again'];
				$args['route']               = $this->check_route( $routes['send_again'] );
				$params['action_send_again'] = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );

				$params['button_title'] = Unlock_Me::get_feature_title();
				$button_disabled        = false;
				if ( ! empty( $ips ) ) {
					// Get IP's.
					$request_count   = get_transient( $this->check_ip_by_remote_addr( $ips[0] ) );
					$button_disabled = false !== $request_count && $request_count >= Unlock_Me::get_attempt_limit();
				}

				$params['button_disabled'] = $button_disabled;
			}

			$this->render_partial(
				'ip-lockout/locked',
				$params
			);
		}

		/**
		 * Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		 * Why?
		 * Escaping this content would break the page.
		 */
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit();
	}

	/**
	 * We will check and prevent the access if the current IP is blacklist, or get temporary banned.
	 *
	 * @param  string $ip  The IP to check.
	 *
	 * @return void|string
	 * @throws InvalidDatabaseException When unexpected data is found in the database.
	 */
	public function maybe_lockout( $ip ) {
		do_action( 'wd_before_lockout', $ip );

		if ( $this->service->skip_priority_lockout_checks( $ip ) ) {
			return;
		}

		$is_blocklisted = $this->service->is_blocklisted_ip( $ip );
		if ( $is_blocklisted['result'] ) {
			// Get Blacklist_Lockout instance.
			$blacklist_model = wd_di()->get( Blacklist_Model::class );
			// This one is get blacklisted.
			$this->actions_for_blocked(
				$blacklist_model->ip_lockout_message,
				0,
				$is_blocklisted['reason'],
				array( $ip )
			);
		}
		// Get an instance of UA component.
		$service_ua = wd_di()->get( User_Agent_Component::class );

		if ( $service_ua->is_active_component() ) {
			$user_agent = $service_ua->sanitize_user_agent();
			if ( $service_ua->is_bad_post( $user_agent ) ) {
				$service_ua->block_user_agent_or_ip( $user_agent, $ip, User_Agent_Component::REASON_BAD_POST );

				return $service_ua->get_message();
			}
			if ( ! empty( $user_agent )
				/**
				 * Apply additional checks for user agent before determining if it is a bad user agent.
				 *
				 * @param  bool  $is_bad_user_agent  The result of checking if the user agent is bad.
				 * @param  string  $user_agent  The user agent string to be checked.
				 * @param  string  $ip  The IP address associated with the user agent.
				 *
				 * @return bool The final result after applying additional checks.
				 * @since 3.1.0
				 */
				&& apply_filters(
					'wd_user_agent_additional_check',
					$service_ua->is_bad_user_agent( $user_agent ),
					$user_agent,
					$ip
				)
			) {
				// Todo: if we use a hook then we should extend cases with a custom reason and send it for log.
				$service_ua->block_user_agent_or_ip( $user_agent, $ip, User_Agent_Component::REASON_BAD_USER_AGENT );

				return $service_ua->get_message();
			}
		}

		$notfound_lockout = wd_di()->get( Notfound_Lockout::class );
		if ( $notfound_lockout->enabled && false === $notfound_lockout->detect_logged && is_user_logged_in() ) {
			/**
			 * We don't need to check the IP if:
			 * the current user can logged-in and isn't from blacklisted,
			 * the option detect_404_logged is disabled.
			 */
			return;
		}
		// Check blacklist.
		$model = Lockout_Ip::get( $ip );
		if ( is_object( $model ) && $model->is_locked() ) {
			$remaining_time = $model->remaining_release_time();
			$this->actions_for_blocked( $model->lockout_message, $remaining_time, 'blacklist', array( $ip ) );
		}
	}

	/**
	 * Remove all IP logs.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function empty_logs(): Response {
		if ( Lockout_Log::truncate() ) {
			$this->log( 'Logs have been successfully deleted.', self::FIREWALL_LOG );

			return new Response(
				true,
				array(
					'message'  => esc_html__( 'Your logs have been successfully deleted.', 'wpdef' ),
					'interval' => 1,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'Failed remove!', 'wpdef' ),
			)
		);
	}

	/**
	 * Return summary data.
	 *
	 * @return array
	 */
	public function get_summary(): array {
		$summary = Lockout_Log::get_summary();

		return array(
			'lockout_last'             => isset( $summary['lockout_last'] ) ?
				$this->format_date_time( $summary['lockout_last'] ) :
				esc_html__( 'Never', 'wpdef' ),
			'lockout_today'            => $summary['lockout_today'] ?? 0,
			'lockout_this_month'       => $summary['lockout_this_month'] ?? 0,
			'lockout_login_today'      => $summary['lockout_login_today'] ?? 0,
			'lockout_login_this_week'  => $summary['lockout_login_this_week'] ?? 0,
			'lockout_login_this_month' => $summary['lockout_login_this_month'] ?? 0,
			'lockout_404_today'        => $summary['lockout_404_today'] ?? 0,
			'lockout_404_this_week'    => $summary['lockout_404_this_week'] ?? 0,
			'lockout_404_this_month'   => $summary['lockout_404_this_month'] ?? 0,
			'lockout_ua_today'         => $summary['lockout_ua_today'] ?? 0,
			'lockout_ua_this_week'     => $summary['lockout_ua_this_week'] ?? 0,
			'lockout_ua_this_month'    => $summary['lockout_ua_this_month'] ?? 0,
		);
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings(): void {
		( new Login_Lockout_Model() )->delete();
		( new Blacklist_Model() )->delete();
		( new Notfound_Lockout() )->delete();
		( new Firewall_Settings() )->delete();
		( new User_Agent_Lockout() )->delete();
		( new Global_Ip_Lockout() )->delete();
		( new Antibot_Global_Firewall_Setting() )->delete();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data(): void {
		Lockout_Log::truncate();
		// Remove cached data.
		Array_Cache::remove( 'countries', 'ip_lockout' );
		// Remove Global IP data.
		( new Global_Ip() )->remove_data();
		// Remove AntiBot Global Firewall data.
		wd_di()->get( Antibot_Global_Firewall::class )->remove_data();
		// Remove Malicious Bot data.
		wd_di()->get( Malicious_Bot::class )->remove_data();
		// Remove Fake Bot data.
		wd_di()->get( Fake_Bot_Detection::class )->remove_data();
		// Remove Firewall Logs data.
		wd_di()->get( Firewall_Logs::class )->remove_data();
		// Clear Trusted Proxy data.
		$trusted_proxy_preset = wd_di()->get( Trusted_Proxy_Preset::class );
		foreach ( array_keys( Firewall_Service::trusted_proxy_presets() ) as $preset ) {
			$trusted_proxy_preset->set_proxy_preset( $preset );
			$trusted_proxy_preset->delete_ips();
		}
		// Remove Unlockouts.
		Unlockout::truncate();
		Smart_Ip_Detection::remove_header();

		delete_site_option( Firewall_Service::WHITELIST_SERVER_PUBLIC_IP_OPTION );
		delete_site_option( Main_Wp::WHITELIST_DASHBOARD_PUBLIC_IP_OPTION );
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$summary_data         = $this->get_summary();
		$user_ip              = $this->get_user_ip();
		$http_ip_header_value = $this->get_user_ip_header();

		$data = array(
			'login'                 => array(
				'month' => $summary_data['lockout_login_this_month'],
				'week'  => $summary_data['lockout_login_this_week'],
				'day'   => $summary_data['lockout_login_today'],
			),
			'nf'                    => array(
				'month' => $summary_data['lockout_404_this_month'],
				'week'  => $summary_data['lockout_404_this_week'],
				'day'   => $summary_data['lockout_404_today'],
			),
			'ua'                    => array(
				'month' => $summary_data['lockout_ua_this_month'],
				'week'  => $summary_data['lockout_ua_this_week'],
				'day'   => $summary_data['lockout_ua_today'],
			),
			'month'                 => $summary_data['lockout_this_month'],
			'day'                   => $summary_data['lockout_today'],
			'last_lockout'          => $summary_data['lockout_last'],
			'settings'              => $this->model->export(),
			'login_lockout'         => wd_di()->get( Login_Lockout_Model::class )->enabled,
			'nf_lockout'            => wd_di()->get( Notfound_Lockout::class )->enabled,
			'report'                => wd_di()->get( Firewall_Report::class )->to_string(),
			'notification_lockout'  => 'enabled' === wd_di()->get( Firewall_Notification::class )->status,
			'ua_lockout'            => wd_di()->get( User_Agent_Lockout::class )->enabled,
			'user_ip'               => implode( ', ', $user_ip ),
			'user_ip_header'        => $http_ip_header_value,
			'trusted_proxy_presets' => Firewall_Service::trusted_proxy_presets(),
			'global_ip'             => wd_di()->get( \WP_Defender\Controller\Global_Ip::class )->data_frontend(),
			'hub_connector'         => wd_di()->get( Hub_Connector::class )->data_frontend(),
			'antibot'               => wd_di()->get( Antibot_Global_Firewall::class )->data_frontend(),
		);

		return array_merge( $data, $this->dump_routes_and_nonces() );
	}

	/**
	 * Provides data for the dashboard widget.
	 *
	 * @return array An array of dashboard widget data.
	 */
	public function dashboard_widget(): array {
		return array(
			'countries' => wd_di()->get( Blacklist_Lockout::class )->get_top_countries_blocked(),
		);
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		$model = $this->model;

		$model->import( $data );
		if ( $model->validate() ) {
			$model->save();
		}
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		$strings         = array();
		$is_pro          = $this->wpmudev->is_pro();
		$firewall_report = new Firewall_Report();
		// Login lockout.
		$strings[] = Login_Lockout_Model::get_module_name() . ' '
					. Login_Lockout_Model::get_module_state( (bool) ( new Login_Lockout_Model() )->enabled );
		// Notfound lockout.
		$strings[] = Notfound_Lockout::get_module_name() . ' '
					. Notfound_Lockout::get_module_state( (bool) ( new Notfound_Lockout() )->enabled );
		// Global IP lockout.
		$strings[] = Global_Ip_Lockout::get_module_name() . ' '
					. Global_Ip_Lockout::get_module_state( (bool) ( new Global_Ip_Lockout() )->enabled );
		// UA lockout.
		$strings[] = User_Agent_Lockout::get_module_name() . ' '
					. User_Agent_Lockout::get_module_state( (bool) ( new User_Agent_Lockout() )->enabled );
		// Notifications and reports.
		if ( 'enabled' === ( new Firewall_Notification() )->status ) {
			$strings[] = esc_html__( 'Email notifications active', 'wpdef' );
		}
		if ( $is_pro && 'enabled' === $firewall_report->status ) {
			$strings[] = sprintf(
			/* translators: %s: Frequency value. */
				esc_html__( 'Email reports sending %s', 'wpdef' ),
				$firewall_report->frequency
			);
		} elseif ( ! $is_pro ) {
			$strings[] = sprintf(
			/* translators: %s: Html for Pro-tag. */
				esc_html__( 'Email report inactive %s', 'wpdef' ),
				'<span class="sui-tag sui-tag-pro">Pro</span>'
			);
		}

		return $strings;
	}

	/**
	 * Generates configuration strings based on the provided configuration and
	 * whether the product is a pro version.
	 *
	 * @param  array $config  Configuration data.
	 * @param  bool  $is_pro  Indicates if the product is a pro version.
	 *
	 * @return array Returns an array of configuration strings.
	 */
	public function config_strings( array $config, bool $is_pro ): array {
		$strings = array();
		// Login lockout.
		if ( isset( $config['login_protection'] ) ) {
			$strings[] = Login_Lockout_Model::get_module_name() . ' '
						. Login_Lockout_Model::get_module_state( (bool) $config['login_protection'] );
		}
		// NF lockout.
		if ( isset( $config['detect_404'] ) ) {
			$strings[] = Notfound_Lockout::get_module_name() . ' '
						. Notfound_Lockout::get_module_state( (bool) $config['detect_404'] );
		}
		// Custom IP List.
		if ( isset( $config['global_ip_list'] ) ) {
			$strings[] = Global_Ip_Lockout::get_module_name() . ' '
						. Global_Ip_Lockout::get_module_state( (bool) $config['global_ip_list'] );
		}
		// UA lockout.
		if ( isset( $config['ua_banning_enabled'] ) ) {
			$strings[] = User_Agent_Lockout::get_module_name() . ' '
						. User_Agent_Lockout::get_module_state( (bool) $config['ua_banning_enabled'] );
		}
		// Notifications.
		if ( isset( $config['notification'] ) && 'enabled' === $config['notification'] ) {
			$strings[] = esc_html__( 'Email notifications active', 'wpdef' );
		}
		// Report.
		if ( $is_pro && 'enabled' === $config['report'] ) {
			$strings[] = sprintf(
			/* translators: %s: Frequency value. */
				esc_html__( 'Email reports sending %s', 'wpdef' ),
				$config['report_frequency']
			);
		} elseif ( ! $is_pro ) {
			$strings[] = sprintf(
			/* translators: %s: Html for Pro-tag. */
				esc_html__( 'Email report inactive %s', 'wpdef' ),
				'<span class="sui-tag sui-tag-pro">Pro</span>'
			);
		}

		return $strings;
	}

	/**
	 * Schedule cleanup blocklist ips event.
	 *
	 * @return void
	 */
	private function schedule_cleanup_blocklist_ips_event() {
		// Sometimes multiple requests come at the same time. So we will only count the web requests.
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) ) {
			return;
		}

		$interval = $this->model->ip_blocklist_cleanup_interval;
		if ( ! $interval || 'never' === $interval ) {
			return;
		}

		$interval_map = array(
			'daily'   => DAY_IN_SECONDS,
			'weekly'  => WEEK_IN_SECONDS,
			'monthly' => MONTH_IN_SECONDS,
		);
		/**
		 * Network Cron Manager
		 *
		 * @var Network_Cron_Manager $network_cron_manager
		 */
		$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
		$network_cron_manager->register_callback(
			'firewall_cleanup_temp_blocklist_ips',
			array( $this->service, 'firewall_clean_up_temporary_ip_blocklist' ),
			$interval_map[ $interval ],
			time() + 15
		);

		$clear = get_site_option( 'wpdef_clear_schedule_firewall_cleanup_temp_blocklist_ips', false );
		if ( $clear ) {
			wp_clear_scheduled_hook( 'firewall_cleanup_temp_blocklist_ips' );
		}
	}

	/**
	 * Maybe add a filter to extend mime types.
	 *
	 * @return void
	 * @since 2.6.3
	 */
	public function maybe_extend_mime_types(): void {
		if ( is_admin() ) {
			$server        = defender_get_data_from_request( null, 's' );
			$current_url   = set_url_scheme( 'http://' . $server['HTTP_HOST'] . $server['REQUEST_URI'] );
			$current_query = wp_parse_url( $current_url, PHP_URL_QUERY );
			$current_query = $current_query ?? '';
			$referer_url   = ! empty( $server['HTTP_REFERER'] ) ?
				filter_var( $server['HTTP_REFERER'], FILTER_SANITIZE_URL ) :
				'';
			$referer_query = wp_parse_url( $referer_url, PHP_URL_QUERY );
			$referer_query = $referer_query ?? '';

			parse_str( $current_query, $current_queries );
			parse_str( $referer_query, $referer_queries );

			if (
				( preg_match( '#^' . network_admin_url() . '#i', $current_url ) &&
					! empty( $current_queries['page'] ) && $this->slug === $current_queries['page']
				) ||
				( preg_match( '#^' . network_admin_url() . '#i', $referer_url ) &&
					! empty( $referer_queries['page'] ) && $this->slug === $referer_queries['page']
				)
			) {
				// Add action hook here.
				add_filter( 'upload_mimes', array( $this, 'extend_mime_types' ) );
			}
		}
	}

	/**
	 * Filter list of allowed mime types and file extensions.
	 *
	 * @param  array $types  List of mime types.
	 *
	 * @return array
	 */
	public function extend_mime_types( array $types ) {
		if ( empty( $types['csv'] ) ) {
			$types['csv'] = 'text/csv';
		}

		return $types;
	}

	/**
	 * Remove all lockouts.
	 *
	 * @return Response
	 * @defender_route
	 * @since 3.3.0
	 */
	public function empty_lockouts() {
		if ( Lockout_Ip::truncate() ) {
			$this->log( 'Deleted lockout records successfully.', self::FIREWALL_LOG );

			return new Response(
				true,
				array(
					'message'  => esc_html__( 'Deleted lockout records successfully.', 'wpdef' ),
					'interval' => 1,
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'Failed remove!', 'wpdef' ),
			)
		);
	}

	/**
	 * Sync IP and it's HTTP header.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function sync_ip_header( Request $request ): Response {
		$data = $request->get_data();

		if ( 'automatic' === $data['ip_detection_type'] ) {
			$this->service_sid->smart_ip_detection_ping( true );

			$ip_detail      = $this->service_sid->get_smart_ip_detection_details();
			$user_ip        = isset( $ip_detail[0] ) ? $ip_detail[0] : '';
			$user_ip_header = isset( $ip_detail[1] ) ? $ip_detail[1] : '';
		} else {
			$remote_addr = wd_di()->get( Remote_Address::class );
			$remote_addr->set_http_ip_header( $data['selected_http_header'] );

			$user_ip        = $remote_addr->get_ip_address();
			$user_ip_header = $remote_addr->get_http_ip_header_value( $data['selected_http_header'] );
		}

		$data = array(
			'user_ip'        => is_array( $user_ip ) ? implode( ', ', $user_ip ) : $user_ip,
			'user_ip_header' => $user_ip_header,
		);

		return new Response(
			true,
			$data
		);
	}

	/**
	 * Prints inline Emoji detection script on specific admin pages only.
	 * The conflict happens when other plugins work with emoji flags.
	 *
	 * @return void
	 * @since 3.7.0
	 */
	public function print_emoji_script(): void {
		$allowed_pages = array(
			$this->slug,
			wd_di()->get( Dashboard::class )->slug,
		);

		if ( in_array( HTTP::get( 'page' ), $allowed_pages, true ) ) {
			if ( ! function_exists( 'print_emoji_detection_script' ) ) {
				include_once ABSPATH . WPINC . '/formatting.php';
			}

			remove_filter( 'emoji_svg_url', '__return_false' );
			print_emoji_detection_script();
		}
	}

	/**
	 * Gather IP(s) from various headers and check if any IP is blacklisted, or temporary banned.
	 *
	 * @return void
	 * @since 4.4.2
	 */
	public function maybe_lockout_gathered_ips(): void {
		$msg = '';
		$ips = $this->service->get_user_ip();

		if ( ! empty( $ips ) && is_array( $ips ) ) {
			foreach ( $ips as $ip ) {
				$result = $this->maybe_lockout( $ip );
				if ( empty( $msg ) && ! empty( $result ) ) {
					$msg = $result;
				}
			}
		}

		if ( ! empty( $msg ) ) {
			$this->actions_for_blocked( $msg, 0, 'blacklist', $ips );
		}
	}

	/**
	 * Clean up old records.
	 *
	 * @return void
	 * @since 4.6.0
	 */
	public function clean_up_unlockout(): void {
		$timestamp = $this->local_to_utc( Unlock_Me::get_expired_time() );
		Unlockout::remove_records( $timestamp, 100 );
	}

	/**
	 * Handle the request to detect the IP header.
	 *
	 * @return void
	 */
	public function handle_detect_ip_header(): void {
		$nonce     = defender_get_data_from_request( 'nonce', 'g' );
		$nonce_ctx = Smart_Ip_Detection::get_nonce_context();

		if ( empty( $nonce ) || get_transient( $nonce_ctx ) !== $nonce ) {
			wp_send_json_error( __( 'Invalid nonce.', 'wpdef' ) );
		}

		delete_transient( $nonce_ctx );

		$result = $this->service_sid->smart_ip_detect_header();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success(
				isset( $result['message'] )
					? $result['message']
					: esc_html__( 'IP detection process completed.', 'wpdef' )
			);
		}
	}

	/**
	 * Unlock user from AntiBot Global Firewall blocklist.
	 *
	 * @return Response
	 * @defender_route
	 * @is_public
	 */
	public function agf_unlock_user(): Response {
		$user_ips            = $this->get_user_ip(); // Get all IPs belonging to the user.
		$attempts_key_prefix = 'wp_defender_agf_unlock_attempts_'; // Prefix for each transient key.

		// Load attempts data and check limits in a single loop.
		foreach ( $user_ips as $ip ) {
			$key           = $attempts_key_prefix . $ip;
			$attempts_data = get_transient( $key );
			$timestamps    = is_array( $attempts_data ) ? $attempts_data : array();

			// Check if the IP has reached the limit.
			if ( count( $timestamps ) >= Unlock_Me::get_attempt_limit() && ( time() - end( $timestamps ) ) < DAY_IN_SECONDS ) {
				$this->log( 'Verification attempt limit reached for IP: ' . $ip, Altcha_Handler::LOG_FILE_NAME );

				return new Response( false, array( 'message' => esc_html__( 'You have reached the maximum limit of verification attempts. Please try again later or contact your web administrator for assistance.', 'wpdef' ) ) );
			}
		}

		// Retrieve captcha payload data.
		$captcha_checkbox = defender_get_data_from_request( 'captcha', 'r' );
		$captcha_payload  = array(
			'algorithm' => defender_get_data_from_request( 'algorithm', 'r' ),
			'challenge' => defender_get_data_from_request( 'challenge', 'r' ),
			'salt'      => defender_get_data_from_request( 'salt', 'r' ),
			'signature' => defender_get_data_from_request( 'signature', 'r' ),
			'number'    => defender_get_data_from_request( 'solution', 'r' ),
		);

		// Successful verification of captcha.
		if ( '0' === $captcha_checkbox && wd_di()->get( Altcha_Handler::class )->verify_solution( $captcha_payload ) ) {
			// Reset attempt data for all IPs in a batch.
			foreach ( $user_ips as $ip ) {
				delete_transient( $attempts_key_prefix . $ip );
			}

			$this->log( 'Captcha verified successfully. IP(s): ' . implode( ', ', $user_ips ), Altcha_Handler::LOG_FILE_NAME );

			$unlock_result = wd_di()->get( Antibot_Global_Firewall_Model::class )->unlock_ips( $user_ips );
			if ( false !== $unlock_result ) {
				wd_di()->get( Antibot_Global_Firewall_Component::class )->log_ip_message( 'Successfully unlocked IP(s): ' . implode( ', ', $user_ips ) );
			}

			return new Response( true, array() );
		}

		// Verification failed: increment attempt count for all IPs.
		$current_time = time();
		foreach ( $user_ips as $ip ) {
			$attempts_key = $attempts_key_prefix . $ip;
			$timestamps   = get_transient( $attempts_key ) ?? array();
			$timestamps[] = $current_time; // Add the current timestamp.
			set_transient( $attempts_key, $timestamps, DAY_IN_SECONDS );
		}

		$this->log( 'Captcha verification failed for IP(s): ' . implode( ', ', $user_ips ), Altcha_Handler::LOG_FILE_NAME );

		return new Response( false, array( 'message' => esc_html__( 'Captcha verification failed. Please try again.', 'wpdef' ) ) );
	}

	/**
	 * Mark the feature page as visited.
	 *
	 * @return void
	 */
	public function mark_page_visited(): void {
		// Breadcrumbs are only for Pro features.
		if ( ! $this->wpmudev->is_pro() ) {
			return;
		}
		if ( 'wdf-ip-lockout' !== defender_get_current_page()
			|| User_Agent_Lockout::get_module_slug() !== defender_get_data_from_request( 'view', 'g' )
		) {
			return;
		}
		// Only for activated UA module.
		if ( wd_di()->get( User_Agent_Lockout::class )->enabled ) {
			wd_di()->get( Breadcrumbs::class )->update_meta_key();
		}
	}
}