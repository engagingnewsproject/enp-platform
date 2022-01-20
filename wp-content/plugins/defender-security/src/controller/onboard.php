<?php

namespace WP_Defender\Controller;

use Calotes\Helper\Array_Cache;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Security_Tweaks\Change_Admin;
use WP_Defender\Component\Security_Tweaks\Disable_File_Editor;
use WP_Defender\Component\Security_Tweaks\Disable_Trackback;
use WP_Defender\Component\Security_Tweaks\Disable_XML_RPC;
use WP_Defender\Component\Security_Tweaks\Hide_Error;
use WP_Defender\Component\Security_Tweaks\Login_Duration;
use WP_Defender\Component\Security_Tweaks\PHP_Version;
use WP_Defender\Component\Security_Tweaks\Prevent_Enum_Users;
use WP_Defender\Component\Security_Tweaks\Security_Key;
use WP_Defender\Component\Security_Tweaks\WP_Version;
use WP_Defender\Controller;
use WP_Defender\Model\Setting\Login_Lockout;
use WP_Defender\Model\Setting\Notfound_Lockout;

/**
 * This class only use once time, after the activation on a fresh install
 * We will use this for activating & presets other module settings
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
		//we dont need all sub menu when in activation mode
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
	 * Enable blacklist status
	 */
	private function preset_blacklist_monitor() {
		$ret = $this->make_wpmu_request( WPMUDEV::API_BLACKLIST, [], [
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
		//create new scan
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
	}

	/**
	 * Resolve all tweaks that we can
	 * @since 2.4.6 Removed tweaks that can be added to wp-config.php manually: 'hide-error', 'disable-file-editor'
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

	/**
	 * Return svg image
	 * @return string
	 */
	private function get_menu_icon() {
		ob_start();
		?>
        <svg width="17px" height="18px" viewBox="10 397 17 18" version="1.1" xmlns="http://www.w3.org/2000/svg"
             xmlns:xlink="http://www.w3.org/1999/xlink">
            <!-- Generator: Sketch 3.8.3 (29802) - http://www.bohemiancoding.com/sketch -->
            <desc>Created with Sketch.</desc>
            <defs></defs>
            <path
                    d="M24.8009393,403.7962 L23.7971393,410.1724 C23.7395393,410.5372 23.5313393,410.8528 23.2229393,411.0532 L18.4001393,413.6428 L13.5767393,411.0532 C13.2683393,410.8528 13.0601393,410.5372 13.0019393,410.1724 L11.9993393,403.7962 L11.6153393,401.3566 C12.5321393,402.9514 14.4893393,405.5518 18.4001393,408.082 C22.3115393,405.5518 24.2675393,402.9514 25.1855393,401.3566 L24.8009393,403.7962 Z M26.5985393,398.0644 C25.7435393,397.87 22.6919393,397.2106 19.9571393,397 L19.9571393,403.4374 L18.4037393,404.5558 L16.8431393,403.4374 L16.8431393,397 C14.1077393,397.2106 11.0561393,397.87 10.2011393,398.0644 C10.0685393,398.0938 9.98213933,398.221 10.0031393,398.3536 L10.8875393,403.969 L11.8913393,410.3446 C12.0071393,411.0796 12.4559393,411.7192 13.1105393,412.0798 L16.8431393,414.1402 L18.4001393,415 L19.9571393,414.1402 L23.6891393,412.0798 C24.3431393,411.7192 24.7925393,411.0796 24.9083393,410.3446 L25.9121393,403.969 L26.7965393,398.3536 C26.8175393,398.221 26.7311393,398.0938 26.5985393,398.0644 L26.5985393,398.0644 Z"
                    id="Defender-Icon" stroke="none" fill="#FFFFFF" fill-rule="evenodd"></path>
        </svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * @return mixed
	 */
	public function remove_settings() {}

	/**
	 * @return mixed
	 */
	public function remove_data() {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return [];
	}
}
