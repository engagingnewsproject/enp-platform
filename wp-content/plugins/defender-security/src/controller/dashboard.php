<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Controller2;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\IO;
use WP_Defender\Component\Feature_Modal;

/**
 * This class will use to create a main admin page.
 *
 * Class Dashboard
 * @package WP_Defender\Controller
 * @method bool is_pro
 */
class Dashboard extends Controller2 {
	use IO, Formats;

	public $slug = 'wp-defender';

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->add_main_page();
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array( &$this, 'menu_order' ) );
		add_action( 'admin_init', array( &$this, 'maybe_redirect_notification_request' ), 99 );
	}

	/**
	 * Because we move the notifications on separate modules, so links from HUB should be redirected to correct URL.
	 *
	 * @return void
	 */
	public function maybe_redirect_notification_request() {
		$page = HTTP::get( 'page' );
		if ( ! in_array( $page, array( 'wdf-scan', 'wdf-ip-lockout', 'wdf-hardener', 'wdf-logging' ), true ) ) {
			return;
		}
		$view = HTTP::get( 'view' );
		if ( in_array( $view, array( 'reporting', 'notification', 'report' ), true ) ) {
			wp_redirect( network_admin_url( 'admin.php?page=wdf-notification' ) );
			exit;
		}
	}

	/**
	 * Filter out the defender menu for changing text.
	 *
	 * @param $menu_order
	 *
	 * @return mixed
	 */
	public function menu_order( $menu_order ) {
		global $submenu;
		if ( isset( $submenu['wp-defender'] ) ) {
			$defender_menu          = $submenu['wp-defender'];
			$defender_menu[0][0]    = esc_html__( 'Dashboard', 'wpdef' );
			$defender_menu          = array_values( $defender_menu );
			$submenu['wp-defender'] = $defender_menu;
		}

		global $menu;
		// Get the total scanning active issues.
		$count = wd_di()->get( \WP_Defender\Component\Scan::class )->indicator_issue_count();

		$indicator = $count > 0
			? ' <span class="update-plugins wd-issue-indicator-sidebar"><span class="plugin-count">' . $count . '</span></span>'
			: null;
		foreach ( $menu as $k => $item ) {
			if ( 'wp-defender' === $item[2] ) {
				$menu[ $k ][0] .= $indicator;
			}
		}

		return $menu_order;
	}

	/**
	 * Determine if we should show the quick setup.
	 * This will show in such scenario:
	 * 1. New setup.
	 * 2. Just upgrade from free.
	 *
	 * @return int
	 * @defender_property
	 */
	public function maybe_show_quick_setup() {
		if ( get_site_transient( 'wp_defender_is_free_activated' ) === 1 ) {
			return 1;
		}

		// Site just created.
		if ( get_site_option( 'wp_defender_shown_activator' ) === false ) {
			return 1;
		}

		return 0;
	}

	protected function add_main_page() {
		$this->register_page(
			$this->get_menu_title(),
			$this->parent_slug,
			array(
				&$this,
				'main_view',
			),
			null,
			$this->get_menu_icon()
		);
	}

	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Enqueue assets & output data.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script( 'def-dashboard', 'dashboard', array_merge( $this->data_frontend(), $this->dump_routes_and_nonces() ) );
		wp_enqueue_script( 'def-dashboard' );
		$this->enqueue_main_assets();
	}

	/**
	 * @param Request $request
	 *
	 * @return Response
	 * @defender_route
	 */
	public function hide_new_features( Request $request ) {
		$data      = $request->get_data(
			array(
				'intention' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$intention = isset( $data['intention'] ) ? $data['intention'] : false;
		switch ( $intention ) {
			case 'notifications':
				delete_site_option( 'wd_show_new_feature' );
				break;
			case 'password_pwned':
				delete_site_option( 'wd_show_feature_password_pwned' );
				break;
			case 'password_reset':
				delete_site_option( 'wd_show_feature_password_reset' );
				break;
			case 'google_recaptcha':
				delete_site_option( 'wd_show_feature_google_recaptcha' );
				break;
			case 'file_extensions':
				delete_site_option( 'wd_show_feature_file_extensions' );
				break;
			case 'user_agent':
				delete_site_option( 'wd_show_feature_user_agent' );
				break;
			case 'woo_recaptcha':
				delete_site_option( 'wd_show_feature_woo_recaptcha' );
				break;
			case 'plugin_vulnerability':
				delete_site_option( 'wd_show_feature_plugin_vulnerability' );
				break;
			default:
				break;
		}

		return new Response( true, array() );
	}

	/**
	 * Return svg image.
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		ob_start();
		?>
		<svg width="17px" height="18px" viewBox="10 397 17 18" version="1.1" xmlns="http://www.w3.org/2000/svg">
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

	public function remove_settings() {
		wd_di()->get( Feature_Modal::class )->upgrade_site_options();
	}

	public function remove_data() {}

	/**
	 * @return array
	 */
	public function data_frontend() {
		list( $endpoints, $nonces ) = Route::export_routes( 'dashboard' );

		return array_merge(
			wd_di()->get( Feature_Modal::class )->get_dashboard_modals(),
			array(
				'scan'              => wd_di()->get( Scan::class )->data_frontend(),
				'firewall'          => wd_di()->get( Firewall::class )->data_frontend(),
				'waf'               => wd_di()->get( WAF::class )->data_frontend(),
				'audit'             => wd_di()->get( Audit_Logging::class )->data_frontend(),
				'blacklist'         => array(
					'nonces'    => $nonces,
					'endpoints' => $endpoints,
				),
				'blocklist_monitor' => wd_di()->get( Blocklist_Monitor::class )->data_frontend(),
				'two_fa'            => wd_di()->get( Two_Factor::class )->data_frontend(),
				'advanced_tools'    => wd_di()->get( Advanced_Tools::class )->data_frontend(),
				'security_tweaks'   => wd_di()->get( Security_Tweaks::class )->data_frontend(),
				'tutorials'         => wd_di()->get( Tutorial::class )->data_frontend(),
				'notifications'     => wd_di()->get( Notification::class )->data_frontend(),
				'settings'          => wd_di()->get( Main_Setting::class )->data_frontend(),
			)
		);
	}

	public function to_array() {}

	public function import_data( $data ) {}

	/**
	 * @return array
	 */
	public function export_strings() {
		return array();
	}
}
