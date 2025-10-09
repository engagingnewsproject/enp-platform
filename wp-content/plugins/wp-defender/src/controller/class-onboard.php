<?php
/**
 * Handles onboarding.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Event;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;
use WP_Defender\Model\Setting\Main_Setting as Model_Main_Setting;
use WP_Defender\Model\Setting\Scan as Scan_Settings;
use WP_Defender\Controller\Scan as Controller_Scan;
use WP_Defender\Controller\Hub_Connector;
use WP_Defender\Model\Setting\Antibot_Global_Firewall_Setting;
use WP_Defender\Component\IP\Antibot_Global_Firewall;
use WP_Defender\Component\Feature_Modal;

/**
 * This class is only used once, after the activation on a fresh install.
 * We will use this for activating & presets other module settings.
 */
class Onboard extends Event {
	/**
	 * The key for the remind later in onboarding page.
	 *
	 * @var string
	 */
	public const REMINDER_KEY = 'wp_defender_onboard_antibot_reminder';

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wp-defender';

	/**
	 * List of steps for the onboarding process.
	 *
	 * @var array
	 */
	public const STEPS = array(
		'init',
		'activating',
		'activate-antibot',
	);

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->add_main_page();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the main page in the WordPress admin menu.
	 */
	protected function add_main_page() {
		$this->register_page(
			$this->get_menu_title(),
			$this->parent_slug,
			array( $this, 'main_view' ),
			null,
			$this->get_menu_icon()
		);
	}

	/**
	 * Renders the main view for this page.
	 */
	public function main_view() {
		$class = wd_di()->get( Security_Tweaks::class );
		$class->refresh_tweaks_status();
		$this->render( 'main' );
	}

	/**
	 * Method to handle the activation process.
	 *
	 * @defender_route
	 */
	public function activating() {
		if ( ! $this->check_permission() || ! $this->verify_nonce( 'activatingonboard' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid', 'wpdef' ) ) );
		}

		$step = defender_get_data_from_request( 'step', 'p' );
		if ( ! in_array( $step, self::STEPS, true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid', 'wpdef' ) ) );
		}

		if ( 'activate-antibot' !== $step ) {
			$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );

			$this->maybe_tracking( 'Activate & Configure' );
			// Run plugin modules.
			if ( $this->is_pro() ) {
				$this->preset_audit();
				$this->preset_blacklist_monitor();
			}
			$this->preset_firewall();
			$this->resolve_security_tweaks();
			$this->preset_scanning();
			// @since 4.2.0 No display the Data Tracking after the Onboarding.
			Data_Tracking::delete_modal_key();

			update_site_option( 'wp_defender_onboarding_step', 'activate-antibot' );

			wp_send_json_success();
		} else {
			$antibot_service = wd_di()->get( Antibot_Global_Firewall::class );
			$managed_by      = $antibot_service->get_default_managed_by();
			$antibot_service->set_managed_by( $managed_by );

			if ( 'plugin' === $managed_by ) {
				$antibot_service->managed_by_plugin_action();
			} else {
				$antibot_service->managed_by_hosting_action();
			}

			update_site_option( 'wp_defender_shown_activator', true );
			delete_site_option( 'wp_defender_is_free_activated' );
			delete_site_option( 'wp_defender_onboarding_step' );

			$data = array(
				'is_antibot_enabled' => $antibot_service->frontend_is_enabled(),
				'hub_connector'      => wd_di()->get( Hub_Connector::class )->data_frontend(),
			);
			// Track.
			if ( $this->is_tracking_active() ) {
				wd_di()->get( \WP_Defender\Helper\Analytics\Antibot::class )
					->track_antibot( false, 'Onboarding' );
			}

			wp_send_json_success( $data );
		}
	}

	/**
	 * Enable blacklist status.
	 */
	private function preset_blacklist_monitor() {
		$this->make_wpmu_request(
			WPMUDEV::API_BLACKLIST,
			array(),
			array(
				'method' => 'POST',
			)
		);
	}

	/**
	 * Sets the audit logging to enabled and saves the changes.
	 *
	 * @return void
	 */
	private function preset_audit() {
		$audit          = new \WP_Defender\Model\Setting\Audit_Logging();
		$audit->enabled = true;
		$audit->save();
	}

	/**
	 * Sets up the preset scanning configuration and creates a new scan.
	 *
	 * @return void
	 */
	private function preset_scanning() {
		$model = new Scan_Settings();
		$model->save();
		// Create new scan.
		$ret = \WP_Defender\Model\Scan::create();
		if ( is_object( $ret ) && ! is_wp_error( $ret ) ) {
			if ( $this->is_tracking_active() ) {
				wd_di()->get( Model_Main_Setting::class )->toggle_tracking( true );
			}
			$scan_controller = wd_di()->get( Controller_Scan::class );

			if ( is_multisite() ) {
				// The admin-ajax.php file doesn't trigger the init hook, so we need to call the scan function directly.
				$scan_controller->process();
			} else {
				$scan_controller->do_async_scan( 'install' );
			}
		}
	}

	/**
	 * Sets up the preset firewall configuration.
	 *
	 * @return void
	 */
	private function preset_firewall() {
		$lockout          = new Login_Lockout();
		$lockout->enabled = true;
		$lockout->save();
		$nf          = new Notfound_Lockout();
		$nf->enabled = true;
		$nf->save();
		$ua          = new User_Agent_Lockout();
		$ua->enabled = true;
		$ua->save();
	}

	/**
	 * Resolve all tweaks that we can.
	 *
	 * @since 2.4.6 Remove tweaks that can be added to wp-config.php manually: 'hide-error', 'disable-file-editor'.
	 */
	private function resolve_security_tweaks() {
		$slugs = array(
			'disable-xml-rpc',
			'login-duration',
			'disable-trackback',
			'prevent-enum-users',
		);
		$class = wd_di()->get( Security_Tweaks::class );
		$class->refresh_tweaks_status();
		$class->security_tweaks_auto_action( $slugs, 'resolve' );
	}

	/**
	 *  Get modules in quick setup.
	 *
	 * @return array
	 */
	private function get_modules(): array {
		$modules = array(
			'Firewall',
			'Recommendations',
		);
		if ( $this->is_pro() ) {
			$modules[] = 'Malware Scanning';
			$modules[] = 'Audit Logging';
			$modules[] = 'Blocklist Monitor';
		} else {
			$modules[] = 'WP file scanning';
		}

		return $modules;
	}

	/**
	 *  Maybe track usage data.
	 *
	 * @param  string $action  Action name.
	 */
	private function maybe_tracking( string $action ) {
		$usage_data_state = HTTP::post( 'usage_tracking', '' );
		// Track it, the default option value is changed to True.
		if ( 'true' === $usage_data_state ) {
			wd_di()->get( Model_Main_Setting::class )->toggle_tracking( true );
			$this->track_opt_toggle( true, 'Wizard' );
			$this->track_feature(
				'def_quick_setup',
				array(
					'module' => $this->get_modules(),
					'action' => $action,
				)
			);
		}
	}

	/**
	 *  Skip onboarding.
	 *
	 * @defender_route
	 */
	public function skip() {
		if ( ! $this->check_permission() || ! $this->verify_nonce( 'skiponboard' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid', 'wpdef' ) ) );
		}

		update_site_option( 'wp_defender_shown_activator', true );
		delete_site_option( 'wp_defender_is_free_activated' );
		// @since 4.2.0 No display the Data Tracking after the Onboarding.
		Data_Tracking::delete_modal_key();

		$this->maybe_tracking( 'Start from scratch' );
		wp_send_json_success();
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}

		wp_localize_script( 'def-onboard', 'onboard', $this->data_frontend() );
		wp_enqueue_script( 'def-onboard' );
		$this->enqueue_main_assets();
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	/**
	 *  Add classes to admin body.
	 *
	 * @param  string $classes  Admin body classes.
	 *
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		$classes .= ' wdf-full-screen ';

		return $classes;
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
		delete_site_option( self::REMINDER_KEY );
		delete_site_option( 'wp_defender_onboarding_step' );
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings() {
		return array();
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		[ $endpoints, $nonces ] = Route::export_routes( 'onboard' );

		return array(
			'endpoints'     => $endpoints,
			'nonces'        => $nonces,
			'misc'          => array(
				'state_usage_tracking' => wd_di()->get( Model_Main_Setting::class )->usage_tracking,
				'privacy_link'         => Model_Main_Setting::PRIVACY_LINK,
				'antibot'              => Antibot_Global_Firewall_Setting::get_module_name(),
			),
			'hub_connector' => wd_di()->get( Hub_Connector::class )->data_frontend(),
			'step'          => get_site_option( 'wp_defender_onboarding_step', 'init' ),
		);
	}

	/**
	 * Remind Antibot onboarding.
	 *
	 * @defender_route
	 */
	public function antibot_reminder() {
		if ( ! $this->check_permission() || ! $this->verify_nonce( 'antibot_reminderonboard' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid', 'wpdef' ) ) );
		}

		update_site_option( 'wp_defender_shown_activator', true );
		update_site_option( self::REMINDER_KEY, time() );
		delete_site_option( 'wp_defender_onboarding_step' );
		delete_site_option( 'wp_defender_is_free_activated' );

		wp_send_json_success();
	}
}