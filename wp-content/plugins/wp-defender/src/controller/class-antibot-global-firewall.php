<?php
/**
 * It handles functionalities such as saving settings and handle the request for downloading the IPs from AntiBot Global Firewall.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Component\Network_Cron_Manager;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Event;
use WP_Defender\Traits\Setting;
use WP_Defender\Traits\Defender_Dashboard_Client;
use WP_Defender\Model\Setting\Antibot_Global_Firewall_Setting;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\IP\Antibot_Global_Firewall as Antibot_Global_Firewall_Component;
use WP_Defender\Component\Blacklist_Lockout;
use WP_Defender\Component\IP\Global_IP;
use WP_Defender\Component\Scheduler\Scheduler;
use WP_Defender\Helper\Analytics\Antibot as Antibot_Analytics;

/**
 * Class Antibot_Global_Firewall.
 */
class Antibot_Global_Firewall extends Event {
	use Setting;
	use Defender_Dashboard_Client;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	protected $slug = 'wdf-ip-lockout';

	/**
	 * The model for handling AntiBot Global Firewall settings.
	 *
	 * @var Antibot_Global_Firewall_Setting
	 */
	protected $model;

	/**
	 * The service for handling AntiBot Global Firewall logic.
	 *
	 * @var Antibot_Global_Firewall_Component
	 */
	protected $service;

	/**
	 * The WPMUDEV instance.
	 *
	 * @var WPMUDEV
	 */
	private $wpmudev;

	/**
	 * Initializes the class with model and service.
	 *
	 * @param Antibot_Global_Firewall_Setting   $model   The model for handling AntiBot Global Firewall settings.
	 * @param Antibot_Global_Firewall_Component $service The service for handling AntiBot Global Firewall logic.
	 * @param WPMUDEV                           $wpmudev The WPMUDEV instance.
	 */
	public function __construct( Antibot_Global_Firewall_Setting $model, Antibot_Global_Firewall_Component $service, WPMUDEV $wpmudev ) {
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );

		$this->model   = $model;
		$this->service = $service;
		$this->wpmudev = $wpmudev;

		add_action( 'wpmudev_hub_connector_first_sync_completed', array( $this, 'maybe_hcm_connection_attempt' ) );

		/**
		 * Download and store Blocklist from the API.
		 */
		if ( $this->service->is_active_via_plugin() ) {
			/**
			 * Network Cron Manager
			 *
			 * @var Network_Cron_Manager $network_cron_manager
			 */
			$network_cron_manager = wd_di()->get( Network_Cron_Manager::class );
			$network_cron_manager->register_callback(
				'wpdef_antibot_global_firewall_fetch_blocklist',
				array( $this, 'handle_download_and_store_blocklist' ),
				12 * HOUR_IN_SECONDS,
				time() + 15
			);
		} elseif ( wp_next_scheduled( 'wpdef_antibot_global_firewall_fetch_blocklist' ) ) {
			wp_clear_scheduled_hook( 'wpdef_antibot_global_firewall_fetch_blocklist' );
		}

		if ( $this->wpmudev->is_wpmu_hosting() ) {
			add_action( 'init', array( $this, 'sync_state' ) );
		}
		add_action( 'init', array( $this, 'handle_expired_membership' ) );
	}

	/**
	 * Handle request to save settings.
	 *
	 * @param Request $request The request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function save_settings( Request $request ) {
		$data = $request->get_data(
			array(
				'enabled'         => array( 'type' => 'bool' ),
				'managed_by'      => array( 'type' => 'string' ),
				// Temporary property.
				'module_title'    => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'module_location' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				// End.
			)
		);
		$old_enabled = (bool) $this->model->enabled;

		$location = 'Feature Page';
		// Split module's titles and locations.
		if ( isset( $data['module_title'] ) && 'antibot' === $data['module_title'] ) {
			if ( empty( $data['module_location'] ) ) {
				$location = 'Dashboard';
			} else {
				$location = $data['module_location'];
				unset( $data['module_location'] );
			}
			unset( $data['module_title'] );
		}

		$this->model->import( $data );
		if ( $this->model->validate() ) {
			$this->model->save();

			if ( 'plugin' === $this->service->get_managed_by() ) {
				if ( $old_enabled !== $this->model->enabled ) {
					if ( ! $this->model->enabled ) {
						$this->service->delete_blocklist();
					} elseif ( $this->model->enabled && $this->service->maybe_download() ) {
						$this->service->download_and_store_blocklist();
					}
				}
			} else {
				$old_enabled = $this->service->frontend_is_enabled();
				$result      = $this->service->toggle_on_hosting( $this->model->enabled );

				if ( is_wp_error( $result ) ) {
					return new Response(
						false,
						array( 'message' => esc_html__( 'Something wrong happened, please try again!', 'wpdef' ) )
					);
				}
			}

			Config_Hub_Helper::set_clear_active_flag();

			// Hide the Antibot notice on the Dashboard page.
			if ( ! empty( $data['enabled'] ) ) {
				delete_site_option( Antibot_Global_Firewall_Component::NOTICE_SLUG );
			}
			// Maybe track.
			if ( ! defender_is_wp_cli() && $old_enabled !== $data['enabled'] ) {
				wd_di()->get( Antibot_Analytics::class )->track_antibot( $old_enabled, $location );
			}

			$update_message = $this->get_update_message( $data, $old_enabled, Antibot_Global_Firewall_Setting::get_module_name() );
			$referrer       = wp_get_referer();
			if ( $referrer && strpos( $referrer, 'page=wp-defender' ) !== false ) {
				$update_message = sprintf(
					/* translators: 1: Bold open tag, 2: Bold close tag */
					__( '%s is now enabled.', 'wpdef' ),
					'<strong>' . Antibot_Global_Firewall_Setting::get_module_name() . '</strong>'
				);
			}
			return new Response(
				true,
				array_merge(
					array(
						'message'    => $update_message,
						'auto_close' => true,
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array( 'message' => $this->model->get_formatted_errors() )
		);
	}

	/**
	 * Hide the Antibot notice.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function hide_antibot_notice(): Response {
		delete_site_option( Antibot_Global_Firewall_Component::NOTICE_SLUG );

		return new Response( true, array() );
	}

	/**
	 * Queue assets and require data.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( $this->is_page_active() ) {
			wp_localize_script( 'def-iplockout', 'antibot', $this->data_frontend() );
		}
	}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend(): array {
		/**
		 * Show the onboarding reminder notice if:
		 * 1. If the reminder time is set and the time difference is greater than a week.
		 * 2. No click on the Cross icon before.
		 */
		$is_reminder   = false;
		$last_reminder = get_site_option( Onboard::REMINDER_KEY, 0 );
		if ( ! empty( $last_reminder ) ) {
			$time_diff = time() - $last_reminder;
			if ( $time_diff > WEEK_IN_SECONDS ) {
				$is_reminder = true;
			}
		}

		$model_export               = $this->model->export();
		$model_export['managed_by'] = $this->service->get_managed_by();
		$module_name                = Antibot_Global_Firewall_Setting::get_module_name();

		return array_merge(
			array(
				'model' => $model_export,
				'misc'  => array(
					'module_slug'           => Antibot_Global_Firewall_Setting::get_module_slug(),
					'module_name'           => $module_name,
					'show_notice'           => $is_reminder
						&& (bool) get_site_option( Antibot_Global_Firewall_Component::NOTICE_SLUG, false ),
					'sync_schedule'         => __( 'Twice Daily', 'wpdef' ),
					'ips_count'             => $this->service->get_blocklisted_ip_count(),
					'frontend_is_enabled'   => $this->service->frontend_is_enabled(),
					'frontend_mode'         => $this->service->frontend_mode(),
					'is_active'             => $this->service->is_active(),
					'show_stats_button'     => ! $this->wpmudev->is_whitelabel_enabled(),
					'show_checker'          => ! $this->wpmudev->is_wpmu_hosting()
						|| $this->wpmudev->is_wpmu_dev_admin()
						|| ! ( $this->service->is_active_via_hosting() && $this->wpmudev->is_whitelabel_enabled() ),
					'active_tooltip_text'   => __( 'List of exploit attempts detected and blocked across all connected sites by AntiBot Firewall.', 'wpdef' ),
					'inactive_tooltip_text' => sprintf(
						/* translators: %s: Module name. */
						__( '%s is Inactive.', 'wpdef' ),
						$module_name
					),
					'current_user'          => esc_html( wp_get_current_user()->display_name ?? __( 'User', 'wpdef' ) ),
					'current_plan'          => $this->get_membership_type(),
					'is_expired_membership' => $this->is_expired_membership_type(),
				),
			),
			$this->dump_routes_and_nonces()
		);
	}

	/**
	 * Download and store blocklist.
	 *
	 * @return void
	 */
	public function handle_download_and_store_blocklist(): void {
		if ( is_multisite() ) {
			$next_run = get_site_option( Antibot_Global_Firewall_Component::DOWNLOAD_SYNC_NEXT_RUN_OPTION, 0 );
			if ( ! empty( $next_run ) && $next_run > time() ) {
				return;
			}

			$interval = wd_di()->get( Scheduler::class )->get_cron_schedule_interval( Antibot_Global_Firewall_Component::DOWNLOAD_SYNC_SCHEDULE );
			$next_run = time() + ( ! empty( $interval ) ? $interval : 12 * HOUR_IN_SECONDS );
			update_site_option( Antibot_Global_Firewall_Component::DOWNLOAD_SYNC_NEXT_RUN_OPTION, $next_run );
		}

		if ( true === $this->service->is_active_via_plugin() ) {
			$is_switch_to_main_site = is_multisite() && ! is_main_site();
			if ( $is_switch_to_main_site ) {
				switch_to_blog( get_main_site_id() );
			}

			$this->service->download_and_store_blocklist();

			if ( $is_switch_to_main_site ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Handle the HCM connection attempt via Antibot page.
	 *
	 * @return void
	 */
	public function maybe_hcm_connection_attempt() {
		$data        = get_site_transient( Hub_Connector::TRANSIENT_KEY );
		$module_slug = $data['module_slug'] ?? '';

		if ( Antibot_Global_Firewall_Setting::get_module_slug() === $module_slug && self::get_status() ) {
			delete_site_transient( Hub_Connector::TRANSIENT_KEY );

			if ( 'plugin' === $this->service->get_managed_by() ) {
				$this->service->managed_by_plugin_action();
			} else {
				$this->service->managed_by_hosting_action();
			}
		}
	}

	/**
	 * Export the data of this module, we will use this for export to HUB, create a preset etc.
	 */
	public function to_array() {}

	/**
	 * Import the data from the HUB, or from the preset.
	 *
	 * @param array $data The data to be imported.
	 *
	 * @return void
	 */
	public function import_data( $data ) {
		$model = $this->model;
		if ( isset( $data['antibot'] ) ) {
			$model->enabled = (bool) $data['antibot'];
		} else {
			$model->enabled = true;
		}
		$model->save();
	}

	/**
	 * Remove all settings, configs generated in this container runtime.
	 */
	public function remove_settings() {}

	/**
	 * Remove all data.
	 */
	public function remove_data() {
		$this->service->delete_blocklist();

		delete_site_option( Antibot_Global_Firewall_Component::NOTICE_SLUG );
		delete_site_option( Antibot_Global_Firewall_Component::DOWNLOAD_SYNC_NEXT_RUN_OPTION );
		delete_site_transient( Antibot_Global_Firewall_Component::BLOCKLIST_STATS_KEY . '_' . Antibot_Global_Firewall_Setting::MODE_BASIC );
		delete_site_transient( Antibot_Global_Firewall_Component::BLOCKLIST_STATS_KEY . '_' . Antibot_Global_Firewall_Setting::MODE_STRICT );
		delete_site_transient( Antibot_Global_Firewall_Component::IS_SWITCHING_TO_PLUGIN_IN_PROGRESS );
	}

	/**
	 * Export strings.
	 *
	 * @return array
	 */
	public function export_strings() {
		return array();
	}

	/**
	 * Disconnect site from HUB.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function disconnect_site(): Response {
		$this->logout();
		return new Response( true, array( 'message' => __( 'Your site has been disconnected successfully!', 'wpdef' ) ) );
	}

	/**
	 * Handle request to switch managed by.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function switch_managed_by(): Response {
		$result = $this->service->switch_managed_by();
		if ( false !== $result ) {
			return new Response(
				true,
				array(
					'message'       => sprintf(
						/* translators: 1. Open tag. 2. Close tag. 3. Managed by label. */
						__( '%1$sAntiBot%2$s blocklist is now being managed by %3$s.', 'wpdef' ),
						'<strong>',
						'</strong>',
						$this->service->get_managed_by_label()
					),
					'managed_by'    => $result,
					'frontend_mode' => $this->service->frontend_mode(),
					'ips_count'     => $this->service->get_blocklisted_ip_count(),
					'auto_close'    => true,
				)
			);
		}

		return new Response(
			false,
			array(
				'message'    => __( 'Failed to switch. Please try again.', 'wpdef' ),
				'auto_close' => true,
			)
		);
	}

	/**
	 * Various actions during form submission.
	 *
	 * @param Request $request  The HTTP request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function blocklist_checker_submit_trusted_ip_form( Request $request ): Response {
		$data = $request->get_data(
			array(
				'ip'      => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'email'   => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'service' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'reason'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		// Logging.
		$message = sprintf(
			'IP: %s, email: %s, service: %s, reason: %s',
			$data['ip'],
			$data['email'],
			$data['service'],
			$data['reason']
		);
		$this->service->log_ip_message( $message );

		return new Response( true, array() );
	}

	/**
	 * Check if an IP exists in the Local, Custom and AntiBot blocklist.
	 *
	 * @param Request $request The HTTP request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function blocklist_checker_search( Request $request ): Response {
		$data = $request->get_data(
			array(
				'ip' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		if ( false === filter_var( $data['ip'], FILTER_VALIDATE_IP ) ) {
			return new Response(
				false,
				array(
					'success'     => false,
					'show_notice' => false,
					'message'     => __( 'Invalid IP address.', 'wpdef' ),
				)
			);
		}

		$bl_service = wd_di()->get( Blacklist_Lockout::class );
		$gi_service = wd_di()->get( Global_IP::class );

		return new Response(
			true,
			array(
				'success'     => true,
				'show_notice' => false,
				'message'     => __( 'The IP address is searched successfully.', 'wpdef' ),
				'local'       => $bl_service->is_blacklist( $data['ip'] ),
				'central'     => $gi_service->is_global_ip_enabled() && $gi_service->is_ip_blocked( $data['ip'] ),
				'antibot'     => $this->service->is_ip_blocked( $data['ip'] ),
			)
		);
	}

	/**
	 * Add IP to AntiBot allowlist.
	 *
	 * @param Request $request The HTTP request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function blocklist_checker_add_ip_to_allowlist( Request $request ): Response {
		$data = $request->get_data(
			array(
				'ip' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$ip   = $data['ip'];
		if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__( 'Invalid IP address.', 'wpdef' ),
				)
			);
		}
		$collection = 'allowlist';
		// Add to Local allowlist.
		$model = wd_di()->get( \WP_Defender\Model\Setting\Blacklist_Lockout::class );
		if ( ! $model->is_ip_in_list( $ip, $collection ) ) {
			$model->add_to_list( $ip, $collection );
		}
		// Add to Custom IP allowlist.
		$global_ip_service = wd_di()->get( Global_IP::class );
		if ( $global_ip_service->can_central_ip_autosync() ) {
			$data = array(
				'allow_list' => array( $ip ),
			);

			$res = $global_ip_service->add_to_global_ip_list( $data );
			if ( is_wp_error( $res ) ) {
				return new Response(
					false,
					array(
						'message' => $res->get_error_message(),
					)
				);
			}
		}

		return new Response(
			true,
			array(
				'message'  => sprintf(
					/* translators: 1: IP address. 2: Opening anchor tag. 3: Closing anchor tag. */
					esc_html__(
						'IP %1$s has been added to your Site\'s allowlist. You can manage it in %2$sIP Lockouts%3$s.',
						'wpdef'
					),
					$ip,
					'<a href="' . network_admin_url( 'admin.php?page=wdf-ip-lockout&view=blocklist#tab-ip-allowlist' ) . '">',
					'</a>'
				),
				'interval' => 5,
			)
		);
	}

	/**
	 * Sync AntiBot status with Hosting.
	 * If AntiBot is enabled in Hosting, update the 'managed_by' and 'enabled' status.
	 * If AntiBot is disabled in Hosting, do nothing.
	 *
	 * @return void
	 */
	public function sync_state(): void {
		$hosting_enabled = defender_get_hosting_feature_state( 'antibot' );

		if (
			true === $hosting_enabled
			&& 'plugin' === $this->model->managed_by
			&& true !== get_site_transient( Antibot_Global_Firewall_Component::IS_SWITCHING_TO_PLUGIN_IN_PROGRESS )
		) {
			$this->model->enabled    = false;
			$this->model->managed_by = 'hosting';
			$this->model->save();
		}
	}

	/**
	 * Handle request to switch mode.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function switch_mode(): Response {
		$result = $this->process_switch_mode();
		if ( $result['success'] ) {
			if ( $this->maybe_track() ) {
				wd_di()->get( Antibot_Analytics::class )->track_antibot( false, 'Feature Page' );
			}
			return new Response( true, $result['data'] );
		}

		return new Response( false, $result['data'] );
	}

	/**
	 * Process request to switch mode.
	 *
	 * @return array
	 */
	public function process_switch_mode(): array {
		$result = $this->service->switch_mode();
		if ( false !== $result && ! is_wp_error( $result ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'message'    => sprintf(
						/* translators: 1. Open tag. 2. Close tag. 3. Mode. */
						__( '%1$sAntiBot Firewall%2$s mode updated – You\'re now using %3$s Mode.', 'wpdef' ),
						'<strong>',
						'</strong>',
						ucfirst( $result )
					),
					'mode'       => $result,
					'ips_count'  => $this->service->get_blocklisted_ip_count(),
					'auto_close' => true,
				),
			);
		}

		return array(
			'success' => false,
			'data'    => array(
				'message'    => __( 'Failed to switch. Please try again.', 'wpdef' ),
				'auto_close' => true,
			),
		);
	}

	/**
	 * Handle expired membership by automatically disabling the AntiBot Global Firewall module.
	 * Logs the action when the feature is disabled due to expired membership.
	 *
	 * @return void
	 */
	public function handle_expired_membership(): void {
		if ( $this->is_expired_membership_type() && $this->model->enabled ) {
			$this->service->managed_by_plugin_action( false );
			$this->log( 'AntiBot Global Firewall automatically disabled due to expired membership.', Antibot_Global_Firewall_Component::LOG_FILE_NAME );
		}
	}
}