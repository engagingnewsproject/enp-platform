<?php
/**
 * Handles the main admin page.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use WP_Defender\Event;
use Calotes\Helper\HTTP;
use Calotes\Helper\Route;
use WP_Defender\Traits\IO;
use Calotes\Component\Request;
use Calotes\Component\Response;
use WP_Defender\Traits\Formats;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component\Feature_Modal;
use WP_Defender\Controller\Hub_Connector;
use WP_Defender\Model\Setting\Global_Ip_Lockout;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Component\IP\Global_IP as Global_IP_Component;
use WP_Defender\Controller\Antibot_Global_Firewall;
use WP_Defender\Model\Setting\Session_Protection;

/**
 * Handles the main admin page.
 */
class Dashboard extends Event {

	use IO;
	use Formats;

	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wp-defender';

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->add_main_page();
		$this->register_routes();
		add_action( 'defender_enqueue_assets', array( $this, 'enqueue_assets' ) );
		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', array( $this, 'menu_order' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_notification_request' ), 99 );
	}

	/**
	 * Because we move the notifications on separate modules, so links from HUB should be redirected to correct URL.
	 *
	 * @return void|null
	 */
	public function maybe_redirect_notification_request() {
		$page = HTTP::get( 'page' );
		if ( ! in_array( $page, array( 'wdf-scan', 'wdf-ip-lockout', 'wdf-hardener', 'wdf-logging' ), true ) ) {
			return;
		}
		$view = HTTP::get( 'view' );
		if ( in_array( $view, array( 'reporting', 'notification', 'report' ), true ) ) {
			wp_safe_redirect( network_admin_url( 'admin.php?page=wdf-notification' ) );
			exit;
		}
	}

	/**
	 * Filter out the defender menu for changing text.
	 *
	 * @param  array $menu_order  The current menu order.
	 *
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		global $submenu;
		if ( isset( $submenu['wp-defender'] ) ) {
			$defender_menu       = $submenu['wp-defender'];
			$defender_menu[0][0] = esc_html__( 'Dashboard', 'wpdef' );
			$defender_menu       = array_values( $defender_menu );
			// Change the global $submenu variable, because otherwise the menu name/order will not change.
			$submenu['wp-defender'] = $defender_menu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		global $menu;
		// Get the total scanning active issues.
		$count = wd_di()->get( \WP_Defender\Component\Scan::class )->indicator_issue_count();

		$indicator = $count > 0
			? ' <span class="update-plugins wd-issue-indicator-sidebar"><span class="plugin-count">' . $count . '</span></span>'
			: null;
		foreach ( $menu as $k => $item ) {
			if ( 'wp-defender' === $item[2] ) {
				// Add a badge next to the "Defender" menu item in the global $menu variable.
				$menu[ $k ][0] .= $indicator; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		return $menu_order;
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
		$this->render( 'main' );
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script(
			'def-dashboard',
			'dashboard',
			array_merge( $this->data_frontend(), $this->dump_routes_and_nonces() )
		);
		wp_enqueue_script( 'def-dashboard' );
		$this->enqueue_main_assets();
	}

	/**
	 * Handles the request to hide new features modal.
	 *
	 * @param  Request $request  The request object containing data.
	 *
	 * @return Response The response object indicating success or failure.
	 * @defender_route
	 */
	public function hide_new_features( Request $request ): Response {
		$data      = $request->get_data(
			array(
				'intention' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$intention = $data['intention'] ?? false;
		if ( 'welcome_modal' === $intention ) {
			Feature_Modal::delete_modal_key();
		}

		return new Response( true, array() );
	}

	/**
	 * Activate Global IP submodule with the enabled Auto sync option.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function activate_global_ip(): Response {
		// Changes for Global IP.
		$model                     = wd_di()->get( Global_Ip_Lockout::class );
		$model->enabled            = true;
		$model->blocklist_autosync = true;
		$model->save();
		// Clear Global IP reminder.
		wd_di()->get( Global_IP_Component::class )->delete_dashboard_notice_reminder();
		// Changes for Hub.
		Config_Hub_Helper::set_clear_active_flag();

		return new Response(
			true,
			array(
				'redirect' => network_admin_url( 'admin.php?page=wdf-ip-lockout&view=global-ip' ),
				'interval' => 1,
			)
		);
	}

	/**
	 * Activate Session Protection submodule.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function activate_session_protection(): Response {
		$model          = wd_di()->get( Session_Protection::class );
		$model->enabled = true;
		$model->save();
		// Changes for Hub.
		Config_Hub_Helper::set_clear_active_flag();

		return new Response(
			true,
			array(
				'redirect' => network_admin_url( 'admin.php?page=wdf-advanced-tools&view=session-protection' ),
				'interval' => 1,
			)
		);
	}

	/**
	 * Remove Global IP notice reminder.
	 *
	 * @return Response
	 * @defender_route
	 */
	public function remove_global_ip_notice_reminder(): Response {
		wd_di()->get( Global_IP_Component::class )->delete_dashboard_notice_reminder();

		return new Response( true, array() );
	}

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
		wd_di()->get( Feature_Modal::class )->upgrade_site_options();
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		[ $endpoints, $nonces ] = Route::export_routes( 'dashboard' );
		$firewall               = wd_di()->get( Firewall::class );

		return array_merge(
			wd_di()->get( Feature_Modal::class )->get_dashboard_modals(),
			array(
				'scan'              => wd_di()->get( Scan::class )->data_frontend(),
				'firewall'          => $firewall->data_frontend(),
				'waf'               => wd_di()->get( WAF::class )->data_frontend(),
				'audit'             => wd_di()->get( Audit_Logging::class )->data_frontend(),
				'blacklist'         => array(
					'nonces'    => $nonces,
					'endpoints' => $endpoints,
				),
				'blocklist_monitor' => wd_di()->get( Blocklist_Monitor::class )->data_frontend(),
				'two_fa'            => wd_di()->get( Two_Factor::class )->data_frontend(),
				'advanced_tools'    => array(
					'mask_login'         => wd_di()->get( Mask_Login::class )->dashboard_widget(),
					'security_headers'   => wd_di()->get( Security_Headers::class )->dashboard_widget(),
					'pwned_passwords'    => wd_di()->get( Password_Protection::class )->dashboard_widget(),
					'recaptcha'          => wd_di()->get( Recaptcha::class )->dashboard_widget(),
					'strong_passwords'   => wd_di()->get( Strong_Password::class )->dashboard_widget(),
					'session_protection' => wd_di()->get( Session_Protection::class )->export(),
				),
				'security_tweaks'   => wd_di()->get( Security_Tweaks::class )->dashboard_widget(),
				'notifications'     => wd_di()->get( Notification::class )->data_frontend(),
				'settings'          => wd_di()->get( Main_Setting::class )->data_frontend(),
				'countries'         => $firewall->dashboard_widget(),
				'global_ip'         => wd_di()->get( Global_Ip::class )->data_frontend(),
				'hub_connector'     => wd_di()->get( Hub_Connector::class )->data_frontend(),
				'antibot'           => wd_di()->get( Antibot_Global_Firewall::class )->data_frontend(),
			)
		);
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
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		return array();
	}
}