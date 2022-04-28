<?php

namespace WP_Defender\Controller;

use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Controller;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;
use WP_Defender\Model\Setting\User_Agent_Lockout;

/**
 * This class is only used once, after the activation on a fresh install.
 * We will use this for activating & presets other module settings.
 *
 * Class Onboard
 * @package WP_Defender\Controller
 */
class Onboard extends Controller {
	public $slug = 'wp-defender';

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->add_main_page();
		add_action( 'defender_enqueue_assets', [ &$this, 'enqueue_assets' ] );
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ &$this, 'menu_order' ] );
	}

	public function menu_order( $menu_order ) {
		global $submenu;
		// We don't need all sub menu when in activation mode.
		unset( $submenu['wp-defender'] );

		return $menu_order;
	}

	protected function add_main_page() {
		$this->register_page( $this->get_menu_title(), $this->parent_slug, [
			&$this,
			'main_view'
		], null, $this->get_menu_icon() );
	}

	public function main_view() {
		$class = wd_di()->get( Security_Tweaks::class );
		$class->refresh_tweaks_status();
		$this->render( 'main' );
	}

	/**
	 * @defender_route
	 */
	public function activating() {
		if ( ! $this->check_permission() || ! $this->verify_nonce( 'activating' . 'onboard' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid', 'wpdef' ),
				)
			);
		}

		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );

		update_site_option( 'wp_defender_shown_activator', true );
		delete_site_option( 'wp_defender_is_free_activated' );
		if ( $this->is_pro() ) {
			$this->preset_audit();
			$this->preset_blacklist_monitor();
		}
		$this->preset_firewall();
		$this->resolve_security_tweaks();
		$this->preset_scanning();

		wp_send_json_success();
	}

	/**
	 * Enable blacklist status.
	 */
	private function preset_blacklist_monitor() {
		$this->make_wpmu_request( WPMUDEV::API_BLACKLIST, [], [
			'method' => 'POST'
		] );
	}

	private function preset_audit() {
		$audit          = new \WP_Defender\Model\Setting\Audit_Logging();
		$audit->enabled = true;
		$audit->save();
	}

	private function preset_scanning() {
		$model = new \WP_Defender\Model\Setting\Scan();
		$model->save();
		// Create new scan.
		$ret = \WP_Defender\Model\Scan::create();
		if ( ! is_wp_error( $ret ) ) {
			wd_di()->get( Scan::class )->do_async_scan( 'install' );
		}
	}

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
	 * @since 2.4.6 Remove tweaks that can be added to wp-config.php manually: 'hide-error', 'disable-file-editor'.
	 */
	private function resolve_security_tweaks() {
		$slugs = [
			'disable-xml-rpc',
			'login-duration',
			'disable-trackback',
			'prevent-enum-users',
		];
		$class = wd_di()->get( Security_Tweaks::class );
		$class->refresh_tweaks_status();
		$class->security_tweaks_auto_action( $slugs, 'resolve' );
	}

	/**
	 * @defender_route
	 */
	public function skip() {
		if ( ! $this->check_permission() || ! $this->verify_nonce( 'skip' . 'onboard' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid', 'wpdef' ),
				)
			);
		}

		update_site_option( 'wp_defender_shown_activator', true );
		delete_site_option( 'wp_defender_is_free_activated' );

		wp_send_json_success();
	}

	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		list( $endpoints, $nonces ) = Route::export_routes( 'onboard' );
		wp_localize_script( 'def-onboard', 'onboard', [
			'endpoints' => $endpoints,
			'nonces'    => $nonces
		] );
		wp_enqueue_script( 'def-onboard' );
		$this->enqueue_main_assets();
	}

	public function remove_settings() {}

	public function remove_data() {}

	public function export_strings() {}

	public function to_array() {}

	public function import_data( $data ) {}

	public function data_frontend() {}
}
