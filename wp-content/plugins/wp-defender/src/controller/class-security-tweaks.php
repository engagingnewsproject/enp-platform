<?php
/**
 * Handles security tweaks operations.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Countable;
use WP_Defender\Event;
use WP_Defender\Admin;
use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\Array_Cache;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\Security_Tweaks\Hide_Error;
use WP_Defender\Component\Security_Tweaks\WP_Version;
use WP_Defender\Component\Security_Tweaks\PHP_Version;
use WP_Defender\Component\Security_Tweaks\Prevent_PHP;
use WP_Defender\Component\Security_Tweaks\Change_Admin;
use WP_Defender\Component\Security_Tweaks\Security_Key;
use WP_Defender\Component\Security_Tweaks\Login_Duration;
use WP_Defender\Component\Security_Tweaks\Servers\Server;
use WP_Defender\Component\Security_Tweaks\Disable_XML_RPC;
use WP_Defender\Component\Security_Tweaks\Disable_Trackback;
use WP_Defender\Component\Security_Tweaks\Prevent_Enum_Users;
use WP_Defender\Component\Security_Tweaks\Disable_File_Editor;
use WP_Defender\Component\Security_Tweaks\Protect_Information;
use WP_Defender\Model\Setting\Security_Tweaks as Model_Security_Tweaks;

/**
 * Contains methods for processing, reverting, ignoring security recommendations, and updating security reminders.
 */
class Security_Tweaks extends Event {

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-hardener';

	/**
	 * The model for handling the data.
	 *
	 * @var Model_Security_Tweaks
	 */
	protected $model;

	/**
	 * Components instance array.
	 *
	 * @var array
	 */
	private $component_instances;

	/**
	 * Instance of Security_Key.
	 *
	 * @var Security_Key
	 */
	private $security_key;

	/**
	 * Instance of Prevent_Enum_Users.
	 *
	 * @var Prevent_Enum_Users
	 */
	private $prevent_enum_users;

	public const STATUS_ISSUES = 'issues', STATUS_RESOLVE = 'fixed', STATUS_IGNORE = 'ignore', STATUS_RESTORE = 'restore';

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->register_page(
			esc_html__( 'Recommendations', 'wpdef' ),
			$this->slug,
			array( $this, 'main_view' ),
			$this->parent_slug
		);
		$this->model = wd_di()->get( Model_Security_Tweaks::class );
		$this->register_routes();

		// Init all the tweaks, should happen one time.
		$this->component_instances = $this->init_tweaks();

		$this->security_key       = $this->component_instances['security-key'];
		$this->prevent_enum_users = $this->component_instances['prevent-enum-users'];

		// Now shield up.
		$this->boot();
		// Add addition hooks.
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_loaded', array( $this, 'should_output_error' ) );
	}

	/**
	 * Dummy function for testing a check.
	 */
	public function should_output_error() {
		if ( empty( defender_get_data_from_request( 'defender_test_error_reporting', 'g' ) ) ) {
			return;
		}
		// It should be only trigger by admin.
		if ( ! $this->check_permission() ) {
			return;
		}

		$var = '$' . uniqid( '', true );
		// This should output a warning. Ignored phpcs as it's a test.
		echo ${$var}; // phpcs:ignore
		exit();
	}

	/**
	 * Process.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function process( Request $request ) {
		$data = $request->get_data(
			array(
				'slug'           => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'current_server' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$slug  = $data['slug'] ?? false;
		$tweak = $this->get_tweak( $slug );

		if ( ! is_object( $tweak ) ) {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Invalid request', 'wpdef' ) )
			);
		}

		if ( in_array( $slug, array( 'prevent-php-executed', 'protect-information' ), true ) ) {
			$current_server = $data['current_server'] ?? false;
			if ( ! $current_server ) {
				return new Response(
					false,
					array( 'message' => esc_html__( 'Invalid request', 'wpdef' ) )
				);
			}

			$ret = $tweak->process( $current_server );
		} else {
			$ret = $tweak->process();
		}

		if ( true === $ret ) {
			Config_Hub_Helper::set_clear_active_flag();
			$this->model->mark( self::STATUS_RESOLVE, $slug );
			// Track.
			$this->track_tweak( $tweak->get_label(), 'Actioned' );
			// Response.
			$this->ajax_response( esc_html__( 'Security recommendation successfully resolved.', 'wpdef' ) );
		}
		if ( is_wp_error( $ret ) ) {
			$this->ajax_response( $ret->get_error_message(), false );
		}

		return new Response(
			false,
			array( 'message' => esc_html__( 'Invalid request', 'wpdef' ) )
		);
	}

	/**
	 * Revert.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function revert( Request $request ) {
		$data    = $request->get_data(
			array(
				'slug'           => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'current_server' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slug    = $data['slug'] ?? false;
		$tweak   = $this->get_tweak( $slug );
		$invalid = array( 'message' => esc_html__( 'Invalid request', 'wpdef' ) );
		if ( ! is_object( $tweak ) ) {
			return new Response(
				false,
				$invalid
			);
		}
		if ( in_array( $slug, array( 'prevent-php-executed', 'protect-information' ), true ) ) {
			$current_server = $data['current_server'] ?? false;
			if ( ! $current_server ) {
				return new Response(
					false,
					$invalid
				);
			}
			$ret = $tweak->revert( $current_server );
		} else {
			$ret = $tweak->revert();
		}

		if ( is_wp_error( $ret ) ) {
			$this->ajax_response( $ret->get_error_message(), false );
		}
		if ( true === $ret ) {
			Config_Hub_Helper::set_clear_active_flag();
			$this->model->mark( self::STATUS_ISSUES, $slug );
			// Track.
			$this->track_tweak( $tweak->get_label(), 'Reverted' );
			// Response.
			$this->ajax_response( esc_html__( 'Security recommendation successfully reverted.', 'wpdef' ) );
		}

		return new Response(
			false,
			$invalid
		);
	}

	/**
	 * Ignore.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response|void
	 * @defender_route
	 */
	public function ignore( Request $request ) {
		$data  = $request->get_data(
			array(
				'slug' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slug  = $data['slug'] ?? false;
		$tweak = $this->get_tweak( $slug );
		if ( ! is_object( $tweak ) ) {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Invalid request', 'wpdef' ) )
			);
		}
		$this->model->mark( self::STATUS_IGNORE, $slug );
		// Track.
		$this->track_tweak( $tweak->get_label(), 'Ignored' );

		$this->security_key->cron_unschedule();

		$this->ajax_response( esc_html__( 'Security recommendation successfully ignored.', 'wpdef' ) );
	}

	/**
	 * Restore.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response|void
	 * @defender_route
	 */
	public function restore( Request $request ) {
		$data  = $request->get_data(
			array(
				'slug' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slug  = $data['slug'] ?? false;
		$tweak = $this->get_tweak( $slug );
		if ( ! is_object( $tweak ) ) {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Invalid request', 'wpdef' ) )
			);
		}
		$this->model->mark( self::STATUS_RESTORE, $slug );
		// Track.
		$this->track_tweak( $tweak->get_label(), 'Restored' );

		if ( $this->security_key->get_is_autogenerate_keys() ) {
			// Mandatory: cron_schedule method bypass scheduling if already a schedule for this job.
			$this->security_key->cron_unschedule();
			$this->security_key->cron_schedule();
		}

		$this->ajax_response( esc_html__( 'Security recommendation successfully restored.', 'wpdef' ) );
	}

	/**
	 * Recheck.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function recheck( Request $request ): Response {
		$data  = $request->get_data(
			array(
				'slug' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slug  = $data['slug'] ?? false;
		$tweak = $this->get_tweak( $slug );

		if ( ! is_object( $tweak ) ) {
			return new Response(
				false,
				array(
					'message' => esc_html__(
						'The status cannot be verified as the request contains an invalid slug.',
						'wpdef'
					),
				)
			);
		}

		$ret = $tweak->check();

		if ( true === $ret ) {
			$this->ajax_response( esc_html__( 'Security recommendation successfully resolved.', 'wpdef' ), true, 1 );
		}

		if ( is_wp_error( $ret ) ) {
			return new Response(
				false,
				array( 'message' => $ret->get_error_message() )
			);
		}

		$msg = sprintf(
		/* translators: %s: Tweak title. */
			esc_html__( '%s is not actioned. Please ensure that all the instructions are followed.', 'wpdef' ),
			$tweak->get_label() ?? ''
		);

		return new Response(
			false,
			array( 'message' => $msg )
		);
	}

	/**
	 * Update security reminder.
	 *
	 * @param Request $request Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function update_security_reminder( Request $request ): Response {
		$data        = $request->get_data();
		$remind_date = $data['remind_date'] ?? false;

		$is_autogen_flag = isset( $data['is_autogenerate_keys'] ) ?
			filter_var( $data['is_autogenerate_keys'], FILTER_VALIDATE_BOOLEAN ) :
			false;

		if ( ! $remind_date ) {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Invalid Reminder frequency', 'wpdef' ) )
			);
		}

		$values = array(
			'reminder_duration'    => $remind_date,
			'reminder_date'        => strtotime( '+' . $remind_date, time() ),
			'is_autogenerate_keys' => $is_autogen_flag,
		);

		if ( update_site_option( 'defender_security_tweaks_' . $this->security_key->slug, $values ) ) {

			if ( true === $is_autogen_flag ) {
				// Mandatory: cron_schedule method bypass scheduling if already a schedule for this job.
				$this->security_key->cron_unschedule();
				$this->security_key->cron_schedule();
			}

			return new Response(
				true,
				array( 'message' => esc_html__( 'Security recommendation successfully updated.', 'wpdef' ) )
			);
		} else {
			return new Response(
				false,
				array( 'message' => esc_html__( 'Error while updating.', 'wpdef' ) )
			);
		}
	}

	/**
	 * AJAX Response handler.
	 *
	 * @param  string   $message  The message to be displayed in the response.
	 * @param  bool     $is_success  Whether the response should have a success status.
	 * @param  bool|int $interval  The cron interval for the security key autogeneration. Default is false.
	 *
	 * @return Response The AJAX response.
	 */
	private function ajax_response( $message, $is_success = true, $interval = false ): Response {
		global $wp_version;

		$settings = new Model_Security_Tweaks();
		$data     = array(
			'message'      => $message,
			'summary'      => array(
				'issues_count' => count( $settings->issues ),
				'fixed_count'  => count( $settings->fixed ),
				'ignore_count' => count( $settings->ignore ),
				'php_version'  => PHP_VERSION,
				'wp_version'   => $wp_version,
			),
			'issues'       => $this->init_tweaks( self::STATUS_ISSUES, 'array' ),
			'fixed'        => $this->init_tweaks( self::STATUS_RESOLVE, 'array' ),
			'ignored'      => $this->init_tweaks( self::STATUS_IGNORE, 'array' ),
			'issues_slugs' => $settings->issues,
		);
		if ( $interval ) {
			$data['interval'] = $interval;
		}

		return new Response( $is_success, $data );
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		wp_localize_script( 'def-securitytweaks', 'security_tweaks', $this->data_frontend() );
		wp_enqueue_script( 'def-securitytweaks' );
		$this->enqueue_main_assets();
	}

	/**
	 * Provides data for the dashboard widget.
	 *
	 * @return array
	 */
	public function dashboard_widget(): array {
		$tweak_arr = $this->model->get_tweak_types();
		$data      = array();
		foreach ( $this->init_tweaks( self::STATUS_ISSUES ) as $slug => $tweak ) {
			$data[] = array(
				'title' => $tweak->get_label(),
				'slug'  => $slug,
			);
		}

		return array(
			'summary' => array(
				'fixed_count'  => $tweak_arr['count_fixed'],
				'ignore_count' => $tweak_arr['count_ignored'],
				'issues_count' => $tweak_arr['count_issues'],
			),
			'issues'  => $data,
		);
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		$this->refresh_tweaks_status();
		global $wp_version;

		$not_allowed_bulk = array(
			'php-version',
			'replace-admin-username',
		);
		if ( 'nginx' === Server::get_current_server() ) {
			$not_allowed_bulk[] = 'protect-information';
			$not_allowed_bulk[] = 'prevent-php-executed';
		}

		$tweak_arr = $this->model->get_tweak_types();

		$data = array(
			'summary'              => array(
				'fixed_count'  => $tweak_arr['count_fixed'],
				'ignore_count' => $tweak_arr['count_ignored'],
				'issues_count' => $tweak_arr['count_issues'],
				'php_version'  => PHP_VERSION,
				'wp_version'   => $wp_version,
			),
			// Todo: improve the logic so that Tweak initialization happens in one go and return 3 different types.
			'issues'               => $this->init_tweaks( self::STATUS_ISSUES, 'array' ),
			'fixed'                => $this->init_tweaks( self::STATUS_RESOLVE, 'array' ),
			'ignored'              => $this->init_tweaks( self::STATUS_IGNORE, 'array' ),
			'not_allowed_bulk'     => $not_allowed_bulk,
			'is_autogenerate_keys' => $this->security_key->get_is_autogenerate_keys(),
			'reminder_frequencies' => $this->security_key->reminder_frequencies(),
			'enabled_user_enums'   => $this->prevent_enum_users->get_enabled_user_enums(),
		);

		return array_merge( $data, $this->dump_routes_and_nonces() );
	}

	/**
	 * Renders the main view for this page.
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Bulk action for security tweaks.
	 * Handles bulk resolving, ignoring, or reverting security tweaks.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function bulk_action( Request $request ) {
		$data      = $request->get_data(
			array(
				'slugs'     => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
				'intention' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slugs     = $data['slugs'] ?? array();
		$intention = $data['intention'] ?? false;
		// Get processed and unprocessed tweaks.
		[ $processed, $unprocessed ] = $this->security_tweaks_auto_action( $slugs, $intention );

		$message = sprintf(
		/* translators: 1: Either ignored or resolved, 2: Count security recommendations */
			esc_html__( 'You have bulk %1$s %2$s security recommendations.', 'wpdef' ),
			'ignore' === $intention ? 'ignored' : 'resolved',
			$processed
		);

		if ( isset( $unprocessed ) && $unprocessed > 0 ) {
			// If we have this case this mean the intention is resolved.
			$message = sprintf(
			/* translators: %d: Count security tweaks */
				esc_html__(
					'You have bulk actioned %d security recommendations. You still have a few unresolved security recommendations, which cannot be bulk actioned automatically, so please address them below.',
					'wpdef'
				),
				$processed
			);

			Config_Hub_Helper::set_clear_active_flag();
		}

		return $this->ajax_response( $message );
	}

	/**
	 * Mass processing.
	 *
	 * @param  array  $slugs  Tweak slugs.
	 * @param  string $intention  Status.
	 *
	 * @return array
	 */
	public function security_tweaks_auto_action( $slugs, $intention ): array {
		$processed   = 0;
		$unprocessed = 0;

		foreach ( $slugs as $slug ) {
			$tweak = $this->get_tweak( $slug );
			if ( 'ignore' === $intention ) {
				$this->model->mark( self::STATUS_IGNORE, $slug );
				// Track.
				$this->track_tweak( $tweak->get_label(), 'Ignored' );
			} elseif ( 'resolve' === $intention ) {
				$wont_do = array(
					'replace-admin-username',
					'prevent-php-executed',
					'wp-version',
					'php-version',
					'protect-information',
				);
				if ( in_array( $slug, $wont_do, true ) ) {
					++$unprocessed;
					continue;
				}
				if ( $tweak->has_method( 'bulk_process' ) ) {
					$ret = $tweak->bulk_process();
				} else {
					$ret = $tweak->process();
				}
				if ( is_wp_error( $ret ) ) {
					$this->ajax_response(
						sprintf(
						/* translators: 1: Security tweak title, 2: Error message */
							esc_html__(
								'There is an error while processing recommendation %1$s, error message: %2$s',
								'wpdef'
							),
							$tweak->get_label(),
							$ret->get_error_message()
						),
						false
					);
				}
				$this->model->mark( self::STATUS_RESOLVE, $slug );
				// Track.
				$this->track_tweak( $tweak->get_label(), 'Actioned' );
			}
			++$processed;
		}

		return array( $processed, $unprocessed );
	}

	/**
	 * Refresh the tweak status and save their state.
	 *
	 * @return void
	 */
	public function refresh_tweaks_status() {
		$tweaks   = $this->init_tweaks();
		$settings = new Model_Security_Tweaks();
		$fixed    = array();
		$issues   = array();

		foreach ( $tweaks as $slug => $class ) {
			if ( $settings->is_tweak_ignore( $slug ) ) {
				continue;
			}

			$is_resolved = $class->check();

			if ( $is_resolved ) {
				$fixed[] = $slug;
			} else {
				$issues[] = $slug;
			}
		}

		$settings->fixed  = $fixed;
		$settings->issues = $issues;
		$settings->save();
	}

	/**
	 * This function for shield every active tweaks up, we will use the cached result.
	 * No check function trigger in this init runtime.
	 */
	private function boot() {
		$tweaks = $this->init_tweaks( self::STATUS_RESOLVE );
		foreach ( $tweaks as $tweak ) {
			$tweak->shield_up();
		}
	}

	/**
	 * Instance all the tweaks, happen one time in init runtime.
	 *
	 * @param  string $type  Type of tweaks.
	 * @param  string $format  Object for internal use, array for frontend use.
	 *
	 * @return array
	 */
	public function init_tweaks( $type = '', $format = 'object' ): array {
		$classes = array(
			Disable_XML_RPC::class,
			WP_Version::class,
			Hide_Error::class,
			PHP_Version::class,
			Change_Admin::class,
			Security_Key::class,
			Login_Duration::class,
			Disable_Trackback::class,
			Prevent_Enum_Users::class,
			Disable_File_Editor::class,
		);
		if ( ! defender_is_wp_cli() ) {
			// We don't load this in cli, as clearly no server is running.
			$classes = array_merge(
				$classes,
				array(
					Protect_Information::class,
					Prevent_PHP::class,
				)
			);
		}

		$tweaks = Array_Cache::get( 'tweaks', 'tweaks' );

		if ( ! is_array( $tweaks ) ) {
			foreach ( $classes as $class ) {
				$obj                  = new $class();
				$tweaks[ $obj->slug ] = $obj;
			}
			Array_Cache::set( 'tweaks', $tweaks, 'tweaks' );
		}
		$tmp = array();
		if ( empty( $type ) ) {
			$tmp = $tweaks;
		} else {
			$settings = new Model_Security_Tweaks();
			$compare  = $settings->$type;
			foreach ( $compare as $slug ) {
				if ( isset( $tweaks[ $slug ] ) ) {
					$tmp[ $slug ] = $tweaks[ $slug ];
				}
			}
		}

		if ( 'array' === $format ) {
			// We need to parse this as array.
			foreach ( $tmp as $slug => $obj ) {
				$arr           = $obj->to_array();
				$arr['status'] = $type;
				$tmp[ $slug ]  = $arr;
			}
		}

		return $tmp;
	}

	/**
	 * Get tweak object from cache by slug.
	 *
	 * @param  string $slug  Tweak slug.
	 *
	 * @return mixed Tweak object if exists, else null.
	 */
	private function get_tweak( $slug ) {
		$tweaks = Array_Cache::get( 'tweaks', 'tweaks' );

		return $tweaks[ $slug ] ?? null;
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		$this->refresh_tweaks_status();
		$settings = new Model_Security_Tweaks();

		return array(
			'count' => array(
				'issues'   => count( $settings->issues ),
				'resolved' => count( $settings->fixed ),
				'total'    => count( $this->init_tweaks() ),
			),
		);
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
		// Revert it first.
		$tweaks = $this->init_tweaks( self::STATUS_RESOLVE );
		// Assign this so internal can use the current server.
		$_POST['current_server'] = Server::get_current_server();
		foreach ( $tweaks as $tweak ) {
			$tweak->revert();

			if ( method_exists( $tweak, 'delete_all_option' ) ) {
				$tweak->delete_all_option();
			}
		}

		( new Model_Security_Tweaks() )->delete();

		delete_site_transient( Server::CACHE_CURRENT_SERVER );
		delete_site_transient( \WP_Defender\Component\Security_Tweaks\Servers\Apache::CACHE_APACHE_VERSION );
		wp_clear_scheduled_hook( 'wpdef_sec_key_gen' );
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
		// Remove cached data.
		Array_Cache::remove( 'tweaks', 'tweaks' );
		delete_site_option( Security_Key::REGENERATE_SALT_NEXT_RUN_OPTION );
	}

	/**
	 * Automate the process of resolving, reverting, and ignoring security tweaks based on the provided data.
	 *
	 * @param  array  $data  The data containing information about fixed, issues, and ignored security tweaks.
	 * @param  string $request_reason  The reason for the request, e.g., 'hub'.
	 *
	 * @return mixed Whether a reauthentication is needed after processing the tweaks.
	 * @since 2.8.1 Add $request_reason param. If there's a request from Hub the plugin doesn't send the error message.
	 */
	public function automate( $data, $request_reason ) {
		$this->refresh_tweaks_status();
		$need_reauth = false;
		// Resolve tweaks.
		if ( ! empty( $data['fixed'] ) ) {
			// There are some tweak that need manual apply, as files based, or change admin.
			$manual_done = array(
				'replace-admin-username',
				'prevent-php-executed',
				'wp-version',
				'php-version',
				'protect-information',
			);
			if ( 'hub' === $request_reason ) {
				$manual_done[] = 'security-key';
			}

			$diff_keys = array_diff( $data['fixed'], $this->model->fixed, $manual_done );
			if ( ! empty( $diff_keys ) ) {
				foreach ( $diff_keys as $slug ) {
					$tweak = $this->get_tweak( $slug );
					if ( $tweak->has_method( 'bulk_process' ) ) {
						$ret = $tweak->bulk_process();
					} else {
						$ret = $tweak->process();
					}

					if ( is_wp_error( $ret ) ) {
						if ( 'hub' === $request_reason ) {
							continue;
						}

						return sprintf(
						/* translators: 1: Security tweak title, 2: Error message */
							esc_html__(
								'There is an error while processing recommendation %1$s, error message: %2$s',
								'wpdef'
							),
							$tweak->get_label(),
							$ret->get_error_message()
						);
					}

					$this->model->mark( self::STATUS_RESOLVE, $slug );
				}
				if ( in_array( 'security-key', $diff_keys, true ) ) {
					$need_reauth = true;
				}
			}
		}
		// Revert tweaks.
		if ( ! empty( $data['issues'] ) ) {
			$diff_keys = array_diff( $data['issues'], $this->model->issues );

			if ( ! empty( $diff_keys ) ) {
				// Issues.
				foreach ( $diff_keys as $slug ) {
					$tweak = $this->get_tweak( $slug );
					$ret   = $tweak->revert();
					if ( is_wp_error( $ret ) ) {
						if ( 'hub' === $request_reason ) {
							continue;
						}

						return sprintf(
						/* translators: 1: Security tweak title, 2: Error message */
							esc_html__(
								'There is an error while processing recommendation %1$s, error message: %2$s',
								'wpdef'
							),
							$tweak->get_label(),
							$ret->get_error_message()
						);
					}
					$this->model->mark( self::STATUS_ISSUES, $slug );
				}
			}
		}
		// Ignore tweaks.
		if ( ! empty( $data['ignore'] ) ) {
			$diff_keys = array_diff( $data['ignore'], $this->model->ignore );
			if ( ! empty( $diff_keys ) ) {
				foreach ( $diff_keys as $slug ) {
					$this->model->mark( self::STATUS_IGNORE, $slug );
				}
			}
		}

		return $need_reauth;
	}

	/**
	 * Imports data into the model.
	 *
	 * @param array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
		$enabled_user_enums = array();

		if ( isset( $data['enabled_user_enums'] ) ) {
			$enabled_user_enums = (array) $data['enabled_user_enums'];

			unset( $data['enabled_user_enums'] );
		}

		$this->prevent_enum_users->set_enabled_user_enums( $enabled_user_enums );

		if ( ! empty( $data['security_key'] ) && is_array( $data['security_key'] ) ) {
			$this->security_key->update_all_option( $data['security_key'] );
		}

		$model = new Model_Security_Tweaks();

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
		$this->refresh_tweaks_status();
		$settings  = new Model_Security_Tweaks();
		$strings   = array();
		$count_all = count( $settings->fixed ) + count( $settings->issues ) + count( $settings->ignore );

		if ( empty( $settings->issues ) ) {
			$strings[] = esc_html__( 'All available recommendations activated', 'wpdef' );
		} else {
			$strings[] = sprintf(
			/* translators: 1: Total security tweaks activated, 2: Total security tweaks */
				esc_html__( '%1$d/%2$d recommendations activated', 'wpdef' ),
				count( $settings->fixed ),
				$count_all
			);
		}

		$tweak_notification = new Tweak_Reminder();
		if ( 'enabled' === $tweak_notification->status ) {
			$strings[] = esc_html__( 'Email notifications active', 'wpdef' );
		}

		return $strings;
	}

	/**
	 * Config strings.
	 *
	 * @param  array $config  Settings.
	 * @param  bool  $is_pro  True if it is a pro version.
	 *
	 * @return array Strings to be displayed in the settings page.
	 */
	public function config_strings( $config, $is_pro ): array {
		$strings = array();
		if ( empty( $config['issues'] ) ) {
			$strings[] = esc_html__( 'All available recommendations activated', 'wpdef' );
		} else {
			$strings[] = sprintf(
			/* translators: 1: Total security tweaks activated, 2: Total security tweaks */
				esc_html__( '%1$d/%2$d recommendations activated', 'wpdef' ),
				is_array( $config['fixed'] ) || $config['fixed'] instanceof Countable ? count( $config['fixed'] ) : 0,
				( is_array( $config['fixed'] ) || $config['fixed'] instanceof Countable ? count( $config['fixed'] ) : 0 )
				+ ( is_array( $config['issues'] ) || $config['issues'] instanceof Countable ? count( $config['issues'] ) : 0 )
				+ ( is_array( $config['ignore'] ) || $config['ignore'] instanceof Countable ? count( $config['ignore'] ) : 0 )
			);
		}
		if ( 'enabled' === $config['notification'] ) {
			$strings[] = esc_html__( 'Email notifications active', 'wpdef' );
		}

		return $strings;
	}

	/**
	 * Update auto generate flag.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @defender_route
	 */
	public function update_autogenerate_flag( Request $request ): Response {
		$data = $request->get_data();

		$is_autogen_flag = isset( $data['is_autogenerate_keys'] ) ?
			filter_var( $data['is_autogenerate_keys'], FILTER_VALIDATE_BOOLEAN ) :
			false;

		$is_success = false;
		$message    = esc_html__( 'An error occurred, try again.', 'wpdef' );

		if ( $this->security_key->set_is_autogenrate_keys( $is_autogen_flag ) ) {
			$is_success = true;

			if ( $is_autogen_flag ) {
				$this->security_key->cron_schedule();
				$message = esc_html__( 'Security key/salt autogenerate enabled.', 'wpdef' );
			} else {
				$this->security_key->cron_unschedule();
				$message = esc_html__( 'Security key/salt autogenerate disabled.', 'wpdef' );
			}
		}

		return new Response(
			$is_success,
			array( 'message' => $message )
		);
	}

	/**
	 * Get component security key instance.
	 *
	 * @return Security_Key
	 */
	public function get_security_key() {
		return $this->security_key;
	}

	/**
	 * Update enabled user enums list.
	 *
	 * @param  Request $request  Request object.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function update_enabled_user_enums( Request $request ): Response {
		$data               = (array) $request->get_data();
		$enabled_user_enums = $data['enabled_user_enums'];
		$is_success         = false;
		$message            = esc_html__( 'An error occurred, try again.', 'wpdef' );

		if ( $this->prevent_enum_users->set_enabled_user_enums( $enabled_user_enums ) ) {
			$is_success = true;
			$message    = esc_html__( 'User enumeration option(s) updated successfully.', 'wpdef' );
		}

		return new Response(
			$is_success,
			array( 'message' => $message )
		);
	}

	/**
	 * Check XML-RPC status.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function check_xml_rpc() {
		$tweak_xml_rpc = wd_di()->get( Disable_XML_RPC::class );
		// If this tweak is ON, block XML-RPC.
		if ( $tweak_xml_rpc->recheck() ) {
			$tweak_xml_rpc->add_hooks();
		}

		return new Response( true, array() );
	}

	/**
	 * Tracks a tweak recommendation.
	 *
	 * @param  string $title  The title of the tweak recommendation.
	 * @param  string $status  The status of the tweak recommendation.
	 *
	 * @return void
	 */
	private function track_tweak( string $title, string $status ) {
		if ( ! defender_is_wp_cli() ) {
			$this->track_feature(
				'def_recommendation_applied',
				array(
					'Recommendation Name' => $title,
					'Status'              => $status,
				)
			);
		}
	}
}