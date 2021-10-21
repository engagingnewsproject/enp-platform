<?php
/**
 * Handle all AJAX actions in admin side
 *
 * @package Hummingbird
 */

namespace Hummingbird\Admin;

use Hummingbird\Core\Configs;
use Hummingbird\Core\Filesystem;
use Hummingbird\Core\Integration\Opcache;
use Hummingbird\Core\Module_Server;
use Hummingbird\Core\Modules\Caching\Preload;
use Hummingbird\Core\Modules\Minify;
use Hummingbird\Core\Modules\Page_Cache;
use Hummingbird\Core\Modules\Performance;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use Hummingbird\WP_Hummingbird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AJAX
 *
 * @package Hummingbird\Admin
 */
class AJAX {

	/**
	 * AJAX constructor.
	 */
	public function __construct() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}

		// React. Hide tutorials.
		add_action( 'wp_ajax_wphb_react_hide_tutorials', array( $this, 'hide_tutorials' ) );

		// Parse clear cache click from frontend admin bar.
		add_action( 'wp_ajax_wphb_front_clear_cache', array( $this, 'clear_frontend_cache' ) );
		// Parse clear full cache from admin notice.
		add_action( 'wp_ajax_wphb_global_clear_cache', array( $this, 'clear_global_cache' ) );
		// Clear selected module cache.
		add_action( 'wp_ajax_wphb_clear_caches', array( $this, 'clear_modules_cache' ) );
		// Clear Cloudflare cache from admin bar.
		add_action( 'wp_ajax_wphb_front_clear_cloudflare', array( $this, 'clear_frontend_cloudflare' ) );

		/**
		 * Multisite global cache clear.
		 *
		 * @since 2.7.0
		 */
		if ( is_multisite() ) {
			// Get the total number of sites in a network.
			add_action( 'wp_ajax_wphb_get_network_sites', array( $this, 'get_network_sites' ) );
			// Clear cache on network subsites.
			add_action( 'wp_ajax_wphb_clear_network_cache', array( $this, 'clear_network_cache' ) );
		}

		/**
		 * DASHBOARD AJAX ACTIONS
		 */

		// Skip quick setup.
		add_action( 'wp_ajax_wphb_dash_skip_setup', array( $this, 'dashboard_skip_setup' ) );
		// Dismiss notice.
		add_action( 'wp_ajax_wphb_notice_dismiss', array( $this, 'notice_dismiss' ) );
		// Dismiss notice.
		add_action( 'wp_ajax_wphb_cf_notice_dismiss', array( $this, 'cf_notice_dismiss' ) );
		// Hide upgrade summary.
		add_action( 'wp_ajax_wphb_hide_upgrade_summary', array( $this, 'hide_upgrade_summary' ) );

		/**
		 * PERFORMANCE TEST AJAX ACTIONS
		 */

		// Run performance test.
		add_action( 'wp_ajax_wphb_performance_run_test', array( $this, 'performance_run_test' ) );
		// Save performance settings.
		add_action( 'wp_ajax_wphb_performance_save_settings', array( $this, 'performance_save_settings' ) );

		/**
		 * CACHING MODULE AJAX ACTIONS
		 */

		// Clear cache.
		add_action( 'wp_ajax_wphb_clear_module_cache', array( $this, 'clear_module_cache' ) );

		/* PAGE CACHING */

		// Save page caching settings.
		add_action( 'wp_ajax_wphb_page_cache_save_settings', array( $this, 'page_cache_save_settings' ) );
		// Gutenberg clear cache for post.
		add_action( 'wp_ajax_wphb_gutenberg_clear_post_cache', array( $this, 'gutenberg_clear_post_cache' ) );
		// Cancel cache preload.
		add_action( 'wp_ajax_wphb_preload_cancel', array( $this, 'cancel_cache_preload' ) );
		// Remove advanced-cache.php file.
		add_action( 'wp_ajax_wphb_remove_advanced_cache', array( $this, 'remove_advanced_cache' ) );

		/* BROWSER CACHING */

		// Activate browser caching.
		add_action( 'wp_ajax_wphb_caching_activate', array( $this, 'caching_activate' ) );
		// Re-check expiry.
		add_action( 'wp_ajax_wphb_caching_recheck_expiry', array( $this, 'caching_recheck_expiry' ) );
		// Set expiration for browser caching.
		add_action( 'wp_ajax_wphb_caching_set_expiration', array( $this, 'caching_set_expiration' ) );
		// Set server type.
		add_action( 'wp_ajax_wphb_caching_set_server_type', array( $this, 'caching_set_server_type' ) );
		// Reload snippet.
		add_action( 'wp_ajax_wphb_caching_reload_snippet', array( $this, 'caching_reload_snippet' ) );
		// Updat htaccess file.
		add_action( 'wp_ajax_wphb_caching_update_htaccess', array( $this, 'caching_update_htaccess' ) );
		// Cloudflare expirtion cache.
		add_action( 'wp_ajax_wphb_cloudflare_set_expiry', array( $this, 'cloudflare_set_expiry' ) );
		// Cloudflare purge cache.
		add_action( 'wp_ajax_wphb_cloudflare_purge_cache', array( $this, 'cloudflare_purge_cache' ) );
		// Cloudflare recheck zones.
		add_action( 'wp_ajax_wphb_cloudflare_recheck_zones', array( $this, 'cloudflare_recheck_zones' ) );

		/* GRAVATAR CACHING */

		/* RSS CACHING */

		// Save settings for rss caching module.
		add_action( 'wp_ajax_wphb_rss_save_settings', array( $this, 'rss_save_settings' ) );

		/* INTEGRATIONS */

		// Save Redis settings.
		add_action( 'wp_ajax_wphb_redis_save_settings', array( $this, 'redis_save_settings' ) );
		// Toggle Redis object cache setting.
		add_action( 'wp_ajax_wphb_redis_toggle_object_cache', array( $this, 'redis_toggle_object_cache' ) );
		add_action( 'wp_ajax_wphb_redis_cache_purge', array( $this, 'redis_cache_purge' ) );
		add_action( 'wp_ajax_wphb_redis_disconnect', array( $this, 'redis_disconnect' ) );

		// Cloudflare connect.
		add_action( 'wp_ajax_wphb_cloudflare_connect', array( $this, 'cloudflare_connect' ) );

		/* CACHE SETTINGS */

		// Parse settings form.
		add_action( 'wp_ajax_wphb_other_cache_save_settings', array( $this, 'save_other_cache_settings' ) );

		/**
		 * ASSET OPTIMIZATION AJAX ACTIONS
		 */

		// Toggle CDN.
		add_action( 'wp_ajax_wphb_minification_toggle_cdn', array( $this, 'minification_toggle_cdn' ) );
		// Toggle logs.
		add_action( 'wp_ajax_wphb_minification_toggle_log', array( $this, 'minification_toggle_log' ) );
		// Toggle advanced minification view.
		add_action( 'wp_ajax_wphb_minification_toggle_view', array( $this, 'minification_toggle_view' ) );
		// Start scan.
		add_action( 'wp_ajax_wphb_minification_start_check', array( $this, 'minification_start_check' ) );
		// Scan check step.
		add_action( 'wp_ajax_wphb_minification_check_step', array( $this, 'minification_check_step' ) );
		// Cancel scan.
		add_action( 'wp_ajax_wphb_minification_cancel_scan', array( $this, 'minification_cancel_scan' ) );
		// Delete scan.
		add_action( 'wp_ajax_wphb_minification_finish_scan', array( $this, 'minification_finish_scan' ) );
		// Save critical css file.
		add_action( 'wp_ajax_wphb_minification_save_critical_css', array( $this, 'minification_save_critical_css' ) );
		// Update custom asset path.
		add_action( 'wp_ajax_wphb_minification_update_asset_path', array( $this, 'minification_update_asset_path' ) );
		// Reset individual file.
		add_action( 'wp_ajax_wphb_minification_reset_asset', array( $this, 'minification_reset_asset' ) );
		// Update settings in network admin.
		add_action( 'wp_ajax_wphb_minification_update_network_settings', array( $this, 'minification_update_network_settings' ) );
		// Save settings.
		add_action( 'wp_ajax_wphb_minification_save_exclude_list', array( $this, 'minification_save_exclude_list' ) );

		// Skip AO upgrade.
		add_action( 'wp_ajax_wphb_ao_skip_upgrade', array( $this, 'minification_skip_upgrade' ) );
		// Perform AO upgrade.
		add_action( 'wp_ajax_wphb_ao_do_upgrade', array( $this, 'minification_do_upgrade' ) );

		/**
		 * ADVANCED TOOLS AJAX ACTIONS
		 */

		// Clean database.
		add_action( 'wp_ajax_wphb_advanced_db_delete_data', array( $this, 'advanced_db_delete_data' ) );
		// Save settings in advanced tools module.
		add_action( 'wp_ajax_wphb_advanced_save_settings', array( $this, 'advanced_save_settings' ) );
		// Purge cache preloader.
		add_action( 'wp_ajax_wphb_advanced_purge_cache', array( $this, 'advanced_purge_cache' ) );
		// Purge asset optimization groups.
		add_action( 'wp_ajax_wphb_advanced_purge_minify', array( $this, 'advanced_purge_minify' ) );
		// Purge asset optimization orphaned data.
		add_action( 'wp_ajax_wphb_advanced_purge_orphaned', array( $this, 'advanced_purge_orphaned' ) );

		/**
		 * LOGGER MODULE AJAX ACTIONS
		 */

		add_action( 'wp_ajax_wphb_logger_clear', array( $this, 'logger_clear' ) );

		/**
		 * SETTINGS MODULE AJAX ACTIONS
		 */

		add_action( 'wp_ajax_wphb_admin_settings_save_settings', array( $this, 'admin_settings_save_settings' ) );
		// Reset settings.
		add_action( 'wp_ajax_wphb_reset_settings', array( $this, 'reset_settings' ) );
		// Toggle tracking.
		add_action( 'wp_ajax_wphb_toggle_tracking', array( $this, 'toggle_tracking' ) );
		// Export settings.
		add_action( 'wp_ajax_wphb_admin_settings_export_settings', array( $this, 'admin_settings_export_settings' ) );
		// Import settings.
		add_action( 'wp_ajax_wphb_admin_settings_import_settings', array( $this, 'admin_settings_import_settings' ) );

		// Configs.
		add_action( 'wp_ajax_wphb_create_config', array( $this, 'save_config' ) );
		add_action( 'wp_ajax_wphb_upload_config', array( $this, 'upload_config' ) );
		add_action( 'wp_ajax_wphb_apply_config', array( $this, 'apply_config' ) );
	}

	/**
	 * Handle clear cache button click from the frontend top admin bar.
	 *
	 * @since 1.9.3
	 */
	public function clear_frontend_cache() {
		$pc_module = Utils::get_module( 'page_cache' );
		$status    = $pc_module->clear_cache();

		if ( ! $status ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Handle clear cache button click from the frontend top admin bar.
	 *
	 * @since 1.9.3
	 */
	public function clear_global_cache() {
		$modules = Utils::get_active_cache_modules();

		foreach ( $modules as $module => $name ) {
			$mod = Utils::get_module( $module );

			if ( ! $mod->is_active() ) {
				continue;
			}

			if ( 'minify' === $module ) {
				$mod->clear_files();
			} else {
				$mod->clear_cache();
			}
		}

		// Remove notice.
		delete_option( 'wphb-notice-cache-cleaned-show' );

		wp_send_json_success();
	}

	/**
	 * Clear cache from selected modules.
	 *
	 * @since 2.7.1
	 */
	public function clear_modules_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		$modules = filter_input( INPUT_POST, 'modules', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! $modules ) {
			wp_send_json_success();
		}

		// Do not clear Varnish cache.
		if ( ! in_array( 'varnish', $modules, true ) ) {
			remove_action( 'wphb_clear_cache_url', array( Utils::get_module( 'page_cache' ), 'clear_external_cache' ) );
		} else {
			$key = array_search( 'varnish', $modules, true );
			unset( $modules[ $key ] );

			// Page cache is disabled in modules... Oh well, force manual purge.
			if ( ! in_array( 'page_cache', $modules, true ) ) {
				Utils::get_api()->varnish->purge_cache( '' );
			}
		}

		// Do not clear Opcache.
		if ( ! in_array( 'opcache', $modules, true ) ) {
			remove_action( 'wphb_clear_cache_url', array( \Hummingbird\Core\Integration\Opcache::get_instance(), 'purge_cache' ) );
		} else {
			$key = array_search( 'opcache', $modules, true );
			unset( $modules[ $key ] );

			// Page cache is disabled in modules... Oh well, force manual purge.
			if ( ! in_array( 'page_cache', $modules, true ) ) {
				Opcache::get_instance()->purge_cache( '' );
			}
		}

		foreach ( $modules as $module ) {
			$mod = Utils::get_module( $module );

			if ( false === $modules ) {
				continue;
			}

			if ( ! $mod->is_active() ) {
				continue;
			}

			if ( 'minify' === $module ) {
				$mod->clear_files();
			} else {
				$mod->clear_cache();
			}
		}

		wp_send_json_success(
			array(
				'message' => __( 'Cache purged.', 'wphb' ),
			)
		);
	}

	/**
	 * Clear Cloudflare cache from admin bar.
	 *
	 * @since 2.7.2
	 */
	public function clear_frontend_cloudflare() {
		$status = Utils::get_module( 'cloudflare' )->clear_cache();

		if ( ! $status ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Hide tutorials on dashboard page.
	 *
	 * @since 2.7.3
	 */
	public function hide_tutorials() {
		check_ajax_referer( 'wphb-fetch' );

		update_option( 'wphb-hide-tutorials', true, false );

		wp_send_json_success();
	}

	/**
	 * Get the number of subsites in a network.
	 *
	 * @since 2.7.0
	 */
	public function get_network_sites() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		global $wpdb;

		$count = wp_cache_get( 'wphb_network_subsites' );

		if ( false === $count ) {
			$count = $wpdb->get_var( "SELECT COUNT( blog_id ) FROM {$wpdb->blogs}" ); // Db call ok.
		}

		wp_cache_set( 'wphb_network_subsites', $count );

		wp_send_json_success( $count );
	}

	/**
	 * Clear a batch of network subsite caches.
	 *
	 * @since 2.7.0
	 */
	public function clear_network_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		// Note: we can not use Utils::get_admin_capability() because is_network_admin() does not work in ajax request.
		if ( ! current_user_can( 'manage_network' ) ) {
			die();
		}

		$sites  = filter_input( INPUT_POST, 'sites', FILTER_SANITIZE_NUMBER_INT );
		$offset = filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT );

		$args = array(
			'number' => (int) $sites,
			'offset' => (int) $offset,
		);

		$sites = get_sites( $args );

		// This is quick hack to force the page cache module to not clear only main site cache.
		$http_host_backup = '';
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$http_host_backup     = wp_unslash( $_SERVER['HTTP_HOST'] );
			$_SERVER['HTTP_HOST'] = '';
		}

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			Utils::get_module( 'page_cache' )->clear_cache( $site->domain . $site->path );
		}

		// Revert the HTTP_HOST value back.
		if ( ! empty( $http_host_backup ) ) {
			$_SERVER['HTTP_HOST'] = $http_host_backup;
		}

		// Reset cached pages count.
		Settings::update_setting( 'pages_cached', 0, 'page_cache' );

		restore_current_blog();
		wp_send_json_success();
	}

	/**
	 * *************************
	 * DASHBOARD AJAX ACTIONS
	 ***************************/

	/**
	 * Skip quick setup and go straight to dashboard.
	 *
	 * @since 1.5.0
	 */
	public function dashboard_skip_setup() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		delete_option( 'wphb_run_onboarding' );

		wp_send_json_success();
	}

	/**
	 * Dismiss notice.
	 *
	 * @since 1.6.1
	 */
	public function notice_dismiss() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['id'] ) ) { // Input var okay.
			die();
		}

		$notice_id = sanitize_text_field( wp_unslash( $_POST['id'] ) ); // Input var ok.

		delete_option( 'wphb-notice-' . $notice_id . '-show' );

		wp_send_json_success();
	}

	/**
	 * Dismiss Cloudflare dash notice.
	 *
	 * @since 1.7.0
	 */
	public function cf_notice_dismiss() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		update_site_option( 'wphb-cloudflare-dash-notice', 'dismissed' );

		wp_send_json_success();
	}

	/**
	 * *************************
	 * PERFORMANCE TEST AJAX ACTIONS
	 ***************************/

	/**
	 * Run performance test.
	 *
	 * Ajax will trigger this method every 3 seconds, until 'finished' = true.
	 * Logic behind this:
	 * - Remove quick setup (if not removed) and init performance scan (if not running)
	 * - Running < 15 seconds  - return control to ajax
	 * - Running 15-89 seconds - check if report is on the server, if not - return to ajax
	 * - Running 90+ seconds   - stop performance test
	 */
	public function performance_run_test() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$started_at = Performance::is_doing_report();
		if ( ! $started_at ) {
			Utils::get_module( 'performance' )->init_scan();
			wp_send_json_success( array( 'finished' => false ) );
		}

		$now = current_time( 'timestamp' );
		if ( $now >= ( $started_at + 15 ) ) {
			$mobile  = '-';
			$desktop = '-';

			// If we're over 1 minute - timeout.
			if ( $now >= ( $started_at + 90 ) ) {
				Performance::set_doing_report( false );
				wp_send_json_success(
					array(
						'finished'     => true,
						'mobileScore'  => $mobile,
						'desktopScore' => $desktop,
					)
				);
			}

			// The report should be finished by this time, let's get the results.
			Performance::refresh_report();
			$report = Performance::get_last_report();

			// Do not cancel the scan if the report is not ready. We might still have some time to wait.
			if ( is_wp_error( $report ) ) {
				// Check if the report is still not available on the server.
				$error = $report->get_error_data( 'performance-error' );
				if ( isset( $error['details'] ) && 'Performance Results not found' === $error['details'] ) {
					Settings::delete( 'wphb-stop-report' );
					wp_send_json_success( array( 'finished' => false ) );
				}
			}

			if ( isset( $report ) && isset( $report->data->mobile->score ) ) {
				$mobile = $report->data->mobile->score;
			}
			if ( isset( $report ) && isset( $report->data->desktop->score ) ) {
				$desktop = $report->data->desktop->score;
			}

			wp_send_json_success(
				array(
					'finished'     => true,
					'mobileScore'  => $mobile,
					'desktopScore' => $desktop,
				)
			);
		}

		// Just do nothing until the report is finished.
		wp_send_json_success( array( 'finished' => false ) );
	}

	/**
	 * Process scan settings.
	 *
	 * @since 1.7.1
	 */
	public function performance_save_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['data'] ) ) { // Input var okay.
			die();
		}

		$performance = Utils::get_module( 'performance' );
		$options     = $performance->get_options();

		// Get the data from ajax.
		parse_str( sanitize_text_field( wp_unslash( $_POST['data'] ) ), $data ); // Input var ok.

		// This option can only be updated on network admin.
		if ( ! is_multisite() || ( is_multisite() && isset( $data['network_admin'] ) && $data['network_admin'] ) ) {
			// I don't like the way this is duplicated in three different modules. This needs to be extracted.
			$options['subsite_tests'] = isset( $data['subsite-tests'] ) && 'super-admins' !== $data['subsite-tests'] ? (bool) $data['subsite-tests'] : 'super-admins';
		}

		$performance->update_options( $options );

		wp_send_json_success();
	}

	/**
	 * *************************
	 * CACHING MODULE AJAX ACTIONS
	 ***************************/

	/**
	 * Purge cache for selected module.
	 *
	 * @since 1.9.0
	 */
	public function clear_module_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['module'] ) ) { // Input var okay.
			die();
		}

		$modules = array( 'page_cache', 'gravatar' );
		$module  = sanitize_text_field( wp_unslash( $_POST['module'] ) ); // Input var ok.

		// Works only for supported modules.
		if ( ! in_array( $module, $modules, true ) ) {
			wp_send_json_success(
				array(
					'success' => false,
				)
			);
		}

		$status = Utils::get_module( $module )->clear_cache();
		wp_send_json_success(
			array(
				'success' => $status,
			)
		);
	}

	/**
	 * Save page caching settings.
	 *
	 * @since 1.9.0
	 */
	public function page_cache_save_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['data'] ) ) { // Input var okay.
			die();
		}

		parse_str( wp_unslash( $_POST['data'] ), $data ); // Input var ok.

		$page_types        = array();
		$custom_post_types = array();

		$url_strings = '';
		$user_agents = '';
		$cookies     = '';

		if ( isset( $data['page_types'] ) && is_array( $data['page_types'] ) ) { // Input var ok.
			$page_types = array_keys( wp_unslash( $data['page_types'] ) ); // Input var ok.
		}

		if ( isset( $data['custom_post_types'] ) && is_array( $data['custom_post_types'] ) ) { // Input var ok.
			$custom_post_types_data = wp_unslash( $data['custom_post_types'] ); // Input var ok.
			foreach ( $custom_post_types_data as $custom_post_type => $value ) {
				if ( $value ) {
					$custom_post_types[] = $custom_post_type;
				}
			}
		}

		$cache_settings = Page_Cache::get_default_settings();
		if ( isset( $data['settings'] ) ) {
			$cache_settings = wp_parse_args( $data['settings'], $cache_settings['settings'] );
		} else {
			$cache_settings = array_map( '__return_false', $cache_settings['settings'] );
		}
		$cache_settings = array_map( 'absint', $cache_settings );

		if ( isset( $data['url_strings'] ) ) { // Input var ok.
			$url_strings = sanitize_textarea_field( wp_unslash( $data['url_strings'] ) ); // Input var okay.
			$url_strings = preg_split( '/[\r\n\t ]+/', $url_strings );

			foreach ( $url_strings as $id => $string ) {
				$string             = str_replace( '\\', '', $string );
				$string             = str_replace( '/', '\/', $string );
				$string             = preg_replace( '/.php$/', '\\.php', $string );
				$string             = preg_replace( '/.xml$/', '\\.xml', $string );
				$string             = preg_replace( '/.yml$/', '\\.yml', $string );
				$url_strings[ $id ] = $string;
			}
		}

		if ( isset( $data['user_agents'] ) ) { // Input var ok.
			$user_agents = sanitize_textarea_field( wp_unslash( $data['user_agents'] ) ); // Input var okay.
			$user_agents = preg_split( '/[\r\n\t]+/', $user_agents );
		}

		if ( isset( $data['cookies'] ) ) { // Input var ok.
			$cookies = sanitize_textarea_field( wp_unslash( $data['cookies'] ) ); // Input var okay.
			$cookies = preg_split( '/[\r\n\t]+/', $cookies );
		}

		$settings['page_types']             = $page_types;
		$settings['custom_post_types']      = $custom_post_types;
		$settings['settings']               = $cache_settings;
		$settings['exclude']['url_strings'] = $url_strings;
		$settings['exclude']['user_agents'] = $user_agents;
		$settings['exclude']['cookies']     = $cookies;

		$module  = Utils::get_module( 'page_cache' );
		$options = $module->get_options();

		if ( isset( $data['settings']['admins_disable_caching'] ) && 1 === absint( $data['settings']['admins_disable_caching'] ) ) {
			$options['enabled'] = 'blog-admins';
		} elseif ( $module->is_active() ) {
			$options['enabled'] = true;
		}

		// Integrations.
		$defaults = Settings::get_default_settings();
		if ( isset( $data['integrations'] ) ) {
			$options['integrations'] = wp_parse_args( $data['integrations'], $defaults['page_cache']['integrations'] );
		} else {
			$options['integrations'] = array_map( '__return_false', $defaults['page_cache']['integrations'] );
		}

		// Cache preload.
		$options['preload'] = isset( $data['preload'] ) && isset( $data['preload']['enabled'] ) ? (bool) $data['preload']['enabled'] : $defaults['page_cache']['preload'];
		if ( $options['preload'] ) {
			$options['preload_type']['home_page'] = isset( $data['preload_type']['home_page'] ) && $data['preload_type']['home_page'];
			$options['preload_type']['on_clear']  = isset( $data['preload_type']['on_clear'] ) && $data['preload_type']['on_clear'];
		}

		// Clear cache interval. Only update, when option is enabled.
		$settings['clear_interval']['enabled'] = isset( $data['clear_interval']['enabled'] ) ? true : false;
		if ( 'days' === $data['clear_interval']['period'] ) {
			$interval = $data['clear_interval']['interval'] * 24;
		} else {
			$interval = $data['clear_interval']['interval'];
		}
		$settings['clear_interval']['interval'] = $interval;

		$module->update_options( $options );
		$module->save_settings( $settings );

		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Clear cache for selected page, when 'clear cache' button is clicked from Gutenberg post edit screen.
	 *
	 * @since 1.9.4
	 */
	public function gutenberg_clear_post_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['postId'] ) ) { // Input var okay.
			die();
		}

		$id = absint( wp_unslash( $_POST['postId'] ) );

		Utils::get_module( 'page_cache' )->clear_cache_action( $id );

		wp_send_json_success();
	}

	/**
	 * Cancel cache preloading.
	 *
	 * @since 2.1.0
	 */
	public function cancel_cache_preload() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			die();
		}

		$preloader = new Preload();
		$preloader->cancel_process();
		wp_send_json_success();
	}

	/**
	 * Remove the advanced-cache.php file.
	 *
	 * @since 3.1.1
	 */
	public function remove_advanced_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			die();
		}

		$adv_cache_file = dirname( get_theme_root() ) . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}

		wp_send_json_success();
	}

	/**
	 * Activate browser caching.
	 *
	 * @since 1.9.0
	 */
	public function caching_activate() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		// Enable caching in .htaccess (only for apache servers).
		$result = Module_Server::save_htaccess( 'caching' );
		if ( $result ) {
			// Clear saved status.
			Utils::get_module( 'caching' )->clear_cache();
			wp_send_json_success(
				array(
					'success' => true,
				)
			);
		}

		wp_send_json_error();
	}

	/**
	 * Re-check browser expiry button clicked.
	 *
	 * @since 1.9.0
	 */
	public function caching_recheck_expiry() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$module = Utils::get_module( 'caching' );

		$cf_module = Utils::get_module( 'cloudflare' );
		// See if we have Cloudflare connected.
		if ( $cf_module->is_connected() && $cf_module->is_zone_selected() ) {
			$expiration = $cf_module->get_caching_expiration( true );
			// Fill the report with values from Cloudflare.
			$values = array_fill_keys( array_keys( $module->get_types() ), $expiration );
		} else {
			// If not - try to get values from header analysis.
			$module->get_analysis_data( true, true );
			$values = $module->status;
		}

		$expiry_values = array_map( array( 'Hummingbird\\Core\\Utils', 'human_read_time_diff' ), $values );

		wp_send_json_success(
			array(
				'success'       => true,
				'expiry_values' => $expiry_values,
			)
		);
	}

	/**
	 * Set expiration for browser caching.
	 */
	public function caching_set_expiration() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['expiry_times'] ) ) { // Input var okay.
			die();
		}

		$sanitized_expiry_times                      = array();
		$sanitized_expiry_times['expiry_javascript'] = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_javascript'] ) ); // Input var ok.
		$sanitized_expiry_times['expiry_css']        = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_css'] ) ); // Input var ok.
		$sanitized_expiry_times['expiry_media']      = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_media'] ) ); // Input var ok.
		$sanitized_expiry_times['expiry_images']     = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_images'] ) ); // Input var ok.

		$caching = Utils::get_module( 'caching' );

		$frequencies = $caching::get_frequencies();

		foreach ( $sanitized_expiry_times as $value ) {
			if ( ! isset( $frequencies[ $value ] ) ) {
				die();
			}
		}

		$options                      = $caching->get_options();
		$options['expiry_css']        = $sanitized_expiry_times['expiry_css'];
		$options['expiry_javascript'] = $sanitized_expiry_times['expiry_javascript'];
		$options['expiry_media']      = $sanitized_expiry_times['expiry_media'];
		$options['expiry_images']     = $sanitized_expiry_times['expiry_images'];
		$caching->update_options( $options );

		/**
		 * Pass in caching type and value into a custom function.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args {
		 *     Array of selected type and value.
		 *
		 *     @type string $type                   Type of cached data, can be one of following:
		 *                                          `javascript`, `css`, `media` or `images`.
		 *     @type array  $sanitized_expiry_times Set expiry values (for example, 1h/A3600), first part can be:
		 *                                          `[n]h` for [n] hours (for example, 1h, 4h, 11h, etc),
		 *                                          `[n]d` for [n] days (for example, 1d, 4d, 11d, etc),
		 *                                          `[n]M` for [n] months (for example, 1M, 4M, 11M, etc),
		 *                                          `[n]y` for [n] years (for example, 1y, 4y, 11y, etc),
		 *                                          second part is the first part in seconds ( 1 hour = 3600 sec).
		 * }
		 */
		do_action(
			'wphb_caching_set_expiration',
			array(
				'expiry_times' => $sanitized_expiry_times,
			)
		);

		wp_send_json_success();
	}

	/**
	 * Set server type.
	 */
	public function caching_set_server_type() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var okay.
			die();
		}

		$value = sanitize_text_field( wp_unslash( $_POST['value'] ) ); // Input var okay.

		if ( ! array_key_exists( $value, Module_Server::get_servers() ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Reload snippet after new expiration interval has been selected.
	 */
	public function caching_reload_snippet() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		if ( ! isset( $_POST['type'] ) || ! isset( $_POST['expiry_times'] ) ) { // Input var okay.
			die();
		}

		$cloudflare = Utils::get_module( 'cloudflare' );

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ) ); // Input var okay.
		// Check if Clouflare value (array won't exist).
		if ( ! strpos( wp_unslash( $_POST['expiry_times']['expiry_javascript'] ), '/A' ) ) { // Input var ok.
			// Convert to readable value.
			$frequency                                   = $cloudflare->convert_frequency( (int) $_POST['expiry_times']['expiry_javascript'] ); // Input var ok.
			$sanitized_expiry_times                      = array();
			$sanitized_expiry_times['expiry_javascript'] = $frequency;
			$sanitized_expiry_times['expiry_css']        = $frequency;
			$sanitized_expiry_times['expiry_media']      = $frequency;
			$sanitized_expiry_times['expiry_images']     = $frequency;
		} else {
			$sanitized_expiry_times                      = array();
			$sanitized_expiry_times['expiry_javascript'] = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_javascript'] ) ); // Input var ok.
			$sanitized_expiry_times['expiry_css']        = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_css'] ) ); // Input var ok.
			$sanitized_expiry_times['expiry_media']      = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_media'] ) ); // Input var ok.
			$sanitized_expiry_times['expiry_images']     = sanitize_text_field( wp_unslash( $_POST['expiry_times']['expiry_images'] ) ); // Input var ok.
		}

		$code = Module_Server::get_code_snippet( 'caching', $type, $sanitized_expiry_times );

		wp_send_json_success(
			array(
				'type' => $type,
				'code' => $code,
			)
		);
	}

	/**
	 * Update htaccess file.
	 *
	 * @since 1.9.0
	 */
	public function caching_update_htaccess() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		Module_Server::unsave_htaccess( 'caching' );

		wp_send_json_success(
			array(
				'success' => Module_Server::save_htaccess( 'caching' ),
			)
		);
	}

	/**
	 * Connect to Cloudflare.
	 *
	 * @since 3.0.0
	 */
	public function cloudflare_connect() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );
		$key   = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_STRING );
		$token = filter_input( INPUT_POST, 'token', FILTER_SANITIZE_STRING );
		$zone  = filter_input( INPUT_POST, 'zone', FILTER_SANITIZE_STRING );

		if ( ! ( $email && $key ) && ! $token && ! $zone ) {
			$message = esc_html__( 'Cannot process the form. Please define either the Email/API key or the API token.', 'wphb' );
			wp_send_json_error( array( 'message' => $message ) );
		}

		$options = Utils::get_module( 'cloudflare' )->get_options();

		$options_updated = false;
		if ( ! empty( $email ) && $email !== $options['email'] ) {
			$options_updated  = true;
			$options['email'] = $email;
		}

		if ( ! empty( $key ) && $key !== $options['api_key'] ) {
			$options_updated    = true;
			$options['api_key'] = $key;
		}

		// Only try to set token if API key is not defined.
		if ( empty( $key ) && ! empty( $token ) && $token !== $options['api_key'] ) {
			$options_updated    = true;
			$options['email']   = ''; // Email is not used with API token.
			$options['api_key'] = $token;
		}

		// Save the current credentials, so we can try and connect with them when we check the zones below.
		if ( $options_updated ) {
			Utils::get_module( 'cloudflare' )->update_options( $options );
			Utils::get_api()->cloudflare->refresh_auth();
		}

		$zones = Utils::get_module( 'cloudflare' )->get_zones_list();

		// This will end processing if zones are an issue.
		Utils::get_module( 'cloudflare' )->validate_zones( $zones );

		// Set the module as enabled.
		if ( ! $options['enabled'] ) {
			$options['enabled'] = true;
			Utils::get_module( 'cloudflare' )->update_options( $options );
		}

		$status = Utils::get_module( 'cloudflare' )->process_zones( $zones, $zone );

		// Could not match a zone.
		if ( ! $status ) {
			wp_send_json_success( array( 'zones' => $zones ) );
		}

		wp_send_json_success();
	}

	/**
	 * Set expiration for Cloudflare cache.
	 */
	public function cloudflare_set_expiry() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var okay.
			die();
		}

		$value = absint( $_POST['value'] ); // Input var ok.

		$result = Utils::get_module( 'cloudflare' )->set_caching_expiration( $value );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error();
			return;
		}

		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Purge Cloudflare cache.
	 */
	public function cloudflare_purge_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		Utils::get_module( 'cloudflare' )->clear_cache();

		wp_send_json_success();
	}

	/**
	 * Recheck Cloudflare zones.
	 */
	public function cloudflare_recheck_zones() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$zones = Utils::get_module( 'cloudflare' )->get_zones_list();

		// This will end processing if zones are an issue.
		Utils::get_module( 'cloudflare' )->validate_zones( $zones );

		$status = Utils::get_module( 'cloudflare' )->process_zones( $zones );

		// Could not match a zone.
		if ( ! $status ) {
			wp_send_json_success( array( 'zones' => $zones ) );
		}

		wp_send_json_success();
	}

	/**
	 * Save rss settings.
	 *
	 * @since 1.8
	 */
	public function rss_save_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['data'] ) ) { // Input var okay.
			die();
		}

		parse_str( sanitize_text_field( wp_unslash( $_POST['data'] ) ), $data ); // Input var ok.

		$rss_module = Utils::get_module( 'rss' );
		$options    = $rss_module->get_options();

		$options['duration'] = isset( $data['rss-expiry-time'] ) ? absint( $data['rss-expiry-time'] ) : 0;

		$rss_module->update_options( $options );
		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Parse save cache settings form.
	 *
	 * @since 1.8.1
	 */
	public function save_other_cache_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['data'] ) ) { // Input var okay.
			die();
		}

		parse_str( sanitize_text_field( wp_unslash( $_POST['data'] ) ), $data ); // Input var ok.

		$pc_module = Utils::get_module( 'page_cache' );
		$options   = $pc_module->get_options();

		$options['detection'] = isset( $data['detection'] ) ? sanitize_text_field( $data['detection'] ) : 'manual';

		// Remove notice if File Change Detection is set to 'auto' or 'none'.
		if ( 'auto' === $options['detection'] || 'none' === $options['detection'] ) {
			delete_option( 'wphb-notice-cache-cleaned-show' );
		}

		$pc_module->update_options( $options );
		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Save Redis cache settings.
	 *
	 * @since 2.5.0
	 */
	public function redis_save_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$host = filter_input( INPUT_POST, 'host', FILTER_SANITIZE_STRING );
		$port = filter_input( INPUT_POST, 'port', FILTER_VALIDATE_INT );
		$pass = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_STRING );
		$db   = filter_input( INPUT_POST, 'db', FILTER_VALIDATE_INT );

		$redis_mod = Utils::get_module( 'redis' );
		$result    = $redis_mod->test_redis_connection( $host, $port, $pass, $db );

		if ( 'success' === $result['status'] ) {
			$redis_mod->enable( $host, $port, $pass, $db );
			wp_send_json_success(
				array(
					'success' => true,
				)
			);
		} else {
			wp_send_json_success(
				array(
					'success' => false,
					'message' => apply_filters( 'wp_hummingbird_redis_error', $result['error'] ),
				)
			);
		}
	}

	/**
	 * Toggle Redis object cache setting.
	 *
	 * @since 2.5.0
	 */
	public function redis_toggle_object_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$value = filter_input( INPUT_POST, 'value', FILTER_VALIDATE_BOOLEAN );

		Utils::get_module( 'redis' )->toggle_object_cache( $value );

		wp_send_json_success( array( 'success' => true ) );
	}

	/**
	 * Purge Redis cache.
	 *
	 * @since 2.5.0
	 */
	public function redis_cache_purge() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		Utils::get_module( 'redis' )->clear_cache();
		wp_send_json_success( array( 'success' => true ) );
	}

	/**
	 * Disconnect Redis.
	 *
	 * @since 2.5.0
	 */
	public function redis_disconnect() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		Utils::get_module( 'redis' )->disable();
		wp_send_json_success( array( 'success' => true ) );
	}

	/**
	 * *************************
	 * ASSET OPTIMIZATION AJAX ACTIONS
	 ***************************/

	/**
	 * Toggle CDN.
	 *
	 * Used on dashboard page in minification meta box and in the minification module.
	 * Clear files function at the end clears all cache and on first home page reload, all the files will
	 * be either moved to CDN or stored local.
	 */
	public function minification_toggle_cdn() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var okay.
			die();
		}

		$value = rest_sanitize_boolean( wp_unslash( $_POST['value'] ) ); // Input var okay.

		$minify_module = Utils::get_module( 'minify' );
		$minify_module->toggle_cdn( $value );
		$minify_module->clear_files();

		wp_send_json_success();
	}

	/**
	 * Toggle logs.
	 *
	 * @since 1.7.2
	 */
	public function minification_toggle_log() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var okay.
			die();
		}

		$value = rest_sanitize_boolean( wp_unslash( $_POST['value'] ) ); // Input var okay.

		$minify         = Utils::get_module( 'minify' );
		$options        = $minify->get_options();
		$options['log'] = $value;
		$minify->update_options( $options );

		wp_send_json_success();
	}

	/**
	 * Toggle minification advanced view.
	 *
	 * @since 1.7.1
	 */
	public function minification_toggle_view() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var okay.
			die();
		}

		$type = sanitize_text_field( wp_unslash( $_POST['value'] ) ); // Input var okay.

		$available_types = array( 'basic', 'advanced' );

		if ( ! in_array( $type, $available_types, true ) ) {
			wp_send_json_error();
		}

		Settings::update_setting( 'view', $type, 'minify' );

		// Hide the modal.
		$hide = filter_input( INPUT_POST, 'hide', FILTER_VALIDATE_BOOLEAN );

		if ( 'basic' === $type ) {
			Utils::get_module( 'minify' )->clear_cache( true, false, true );
			if ( true === $hide ) {
				delete_option( 'wphb-minification-show-config_modal' );
			}
		} elseif ( true === $hide ) {
			delete_option( 'wphb-minification-show-advanced_modal' );
		}

		wp_send_json_success();
	}

	/**
	 * Start minification scan.
	 *
	 * Set a flag that marks the minification check files as started.
	 */
	public function minification_start_check() {
		$minify_module = Utils::get_module( 'minify' );
		$minify_module->init_scan();

		wp_send_json_success(
			array(
				'steps' => $minify_module->scanner->get_scan_steps(),
			)
		);
	}

	/**
	 * Process step during minification scan.
	 */
	public function minification_check_step() {
		$minify_module = Utils::get_module( 'minify' );

		$urls         = $minify_module->scanner->get_scan_urls();
		$current_step = absint( $_POST['step'] ); // Input var ok.

		$minify_module->scanner->update_current_step( $current_step );

		if ( isset( $urls[ $current_step ] ) ) {
			$minify_module->scanner->scan_url( $urls[ $current_step ] );
		}

		wp_send_json_success();
	}

	/**
	 * Cancel minification file check if cancel button pressed.
	 *
	 * @since 1.4.5
	 */
	public function minification_cancel_scan() {
		$minify_module = Utils::get_module( 'minify' );
		$minify_module->toggle_service( false );
		$minify_module->clear_cache();

		wp_send_json_success();
	}

	/**
	 * Finish minification scan.
	 */
	public function minification_finish_scan() {
		delete_transient( 'wphb-minification-files-scanning' );
		update_option( 'wphb-minification-files-scanned', true );
		wp_send_json_success(
			array(
				'assets_msg' => sprintf(
					/* translators: %s - number of assets */
					esc_html__( '%s assets found!', 'wphb' ),
					Utils::minified_files_count()
				),
			)
		);
	}

	/**
	 * Save critical css on minification tools window.
	 *
	 * @since 1.8
	 */
	public function minification_save_critical_css() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['form'] ) ) { // Input var okay.
			die();
		}

		parse_str( wp_unslash( $_POST['form'] ), $form ); // Input var ok.

		$status = Minify::save_css( $form['critical_css'] );

		wp_send_json_success(
			array(
				'success' => $status['success'],
				'message' => $status['message'],
			)
		);
	}

	/**
	 * Parse custom asset path directory.
	 *
	 * @since 1.9
	 */
	public function minification_update_asset_path() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var ok.
			die();
		}

		$path = sanitize_text_field( wp_unslash( $_POST['value'] ) ); // Input var ok.

		Utils::get_module( 'minify' )->clear_cache( false );

		$current_path = Filesystem::instance()->resolve_minify_asset_path();

		if ( isset( $current_path ) && ! empty( $current_path ) ) {
			Filesystem::instance()->purge( $current_path, true );
		}

		// Update to new setting value.
		Settings::update_setting( 'file_path', $path, 'minify' );

		wp_send_json_success(
			array(
				'success' => true,
				'message' => '',
			)
		);
	}

	/**
	 * Reset individual file.
	 *
	 * @since 1.9.2
	 */
	public function minification_reset_asset() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['value'] ) ) { // Input var ok.
			die();
		}

		$files = explode( ' ', sanitize_text_field( wp_unslash( $_POST['value'] ) ) ); // Input var ok.

		$type = $handle = '';
		foreach ( $files as $item ) {
			if ( 'css' === strtolower( $item ) ) {
				$type = 'styles';
				continue;
			}

			if ( 'js' === strtolower( $item ) ) {
				$type = 'scripts';
				continue;
			}

			$handle = $item;
		}

		if ( ! $handle || ! $type ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error removing asset file.', 'wphb' ),
				)
			);
		}

		Utils::get_module( 'minify' )->clear_file( $handle, $type );

		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Update network settings.
	 *
	 * @since 2.0.0
	 */
	public function minification_update_network_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['settings'] ) ) { // Input var okay.
			die();
		}

		wp_parse_str( sanitize_text_field( wp_unslash( $_POST['settings'] ) ), $form );

		if ( isset( $form['enabled'] ) && 'super-admins' !== $form['enabled'] ) {
			$form['enabled'] = (bool) $form['enabled'];
		}

		$minify  = Utils::get_module( 'minify' );
		$options = $minify->get_options();

		$options['use_cdn'] = isset( $form['use_cdn'] ) ? (bool) $form['use_cdn'] : false;
		$options['log']     = isset( $form['log'] ) ? (bool) $form['log'] : false;

		$minify->update_options( $options );
		if ( ! isset( $form['network'] ) ) {
			$minify->toggle_service( false, true );
		} else {
			$minify->toggle_service( $form['enabled'], true );
		}

		wp_send_json_success(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Update the CDN exclude list
	 *
	 * @since 2.4.0
	 */
	public function minification_save_exclude_list() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['data'] ) ) {
			die();
		}

		$assets = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_STRING );
		$assets = json_decode( html_entity_decode( $assets ), true );

		Settings::update_setting( 'nocdn', $assets, 'minify' );
		// This will require a clear cache call.
		Utils::get_module( 'minify' )->clear_cache( false );

		wp_send_json_success();
	}

	/**
	 * Skip Asset Optimization upgrade.
	 *
	 * @since 2.6.0
	 */
	public function minification_skip_upgrade() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		// Switch to advanced mode.
		Settings::update_setting( 'view', 'advanced', 'minify' );
		// Remove the upgrade modal.
		delete_option( 'wphb_do_minification_upgrade' );

		wp_send_json_success();
	}

	/**
	 * Perform Asset Optimization upgrade.
	 *
	 * @since 2.6.0
	 */
	public function minification_do_upgrade() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		Settings::update_setting( 'view', 'basic', 'minify' );
		Utils::get_module( 'minify' )->clear_cache( true, true, true );

		// Remove the upgrade modal.
		delete_option( 'wphb_do_minification_upgrade' );

		wp_send_json_success();
	}

	/**
	 * *************************
	 * ADVANCED TOOLS AJAX ACTIONS
	 ***************************/

	/**
	 * Cleanup selected data type from db.
	 *
	 * @since 1.8
	 */
	public function advanced_db_delete_data() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['data'] ) ) { // Input var okay.
			die();
		}

		$available_types = array( 'revisions', 'drafts', 'trash', 'spam', 'trash_comment', 'expired_transients', 'transients', 'all' );
		$type            = sanitize_text_field( wp_unslash( $_POST['data'] ) ); // Input var ok.

		if ( ! in_array( $type, $available_types, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid type specified.', 'wphb' ),
				)
			);
		}

		$adv_module = Utils::get_module( 'advanced' );
		$removed    = $adv_module->delete_db_data( $type );

		if ( ! is_array( $removed ) || ( 0 === $removed['items'] && 0 > $removed['left']->total ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Error deleting data.', 'wphb' ),
				)
			);
		}

		wp_send_json_success(
			array(
				/* translators: %d: number of database entries */
				'message' => sprintf( __( '<strong>%d database entries</strong> were deleted successfully.', 'wphb' ), $removed['items'] ),
				'left'    => $removed['left'],
			)
		);
	}

	/**
	 * Update settings for advanced tools.
	 *
	 * @since 1.8
	 */
	public function advanced_save_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['form'] ) ) { // Input var okay.
			wp_die();
		}

		$form = sanitize_text_field( wp_unslash( $_POST['form'] ) ); // Input var ok.
		parse_str( wp_unslash( $_POST['data'] ), $data ); // Input var ok.

		$adv_module = Utils::get_module( 'advanced' );
		$options    = $adv_module->get_options();

		// General settings tab.
		if ( 'advanced-general-settings' === $form ) {
			$skip = isset( $options['query_strings_global'] ) && $options['query_strings_global'] && ! Utils::is_ajax_network_admin();
			if ( ! $skip ) {
				$options['query_string']         = isset( $data['query_strings'] ) && 'on' === $data['query_strings'];
				$options['query_strings_global'] = isset( $data['query_strings_global'] ) && 'on' === $data['query_strings_global'];
			}

			if ( isset( $data['cart_fragments'] ) && 'on' === $data['cart_fragments'] ) {
				$options['cart_fragments'] = isset( $data['cart_fragments_value'] ) && '1' === $data['cart_fragments_value'] ? true : 'all';
			} else {
				$options['cart_fragments'] = false;
			}

			$skip = isset( $options['emoji_global'] ) && $options['emoji_global'] && ! Utils::is_ajax_network_admin();
			if ( ! $skip ) {
				$options['emoji']        = isset( $data['emojis'] ) && 'on' === $data['emojis'];
				$options['emoji_global'] = isset( $data['emojis_global'] ) && 'on' === $data['emojis_global'];
			}

			$options['prefetch'] = array();
			if ( isset( $data['url_strings'] ) && ! empty( $data['url_strings'] ) ) {
				$options['prefetch'] = preg_split( '/[\r\n\t ]+/', $data['url_strings'] );
			}

			$options['preconnect'] = array();
			if ( isset( $data['preconnect_strings'] ) && ! empty( $data['preconnect_strings'] ) ) {
				$options['preconnect'] = preg_split( '/[\r\n\t]+/', $data['preconnect_strings'] );
			}
		}

		// Database cleanup settings tab.
		if ( 'advanced-db-settings' === $form ) {
			$tables = array(
				'revisions'          => isset( $data['revisions'] ) && 'on' === $data['revisions'],
				'drafts'             => isset( $data['drafts'] ) && 'on' === $data['drafts'],
				'trash'              => isset( $data['trash'] ) && 'on' === $data['trash'],
				'spam'               => isset( $data['spam'] ) && 'on' === $data['spam'],
				'trash_comment'      => isset( $data['trash_comment'] ) && 'on' === $data['trash_comment'],
				'expired_transients' => isset( $data['expired_transients'] ) && 'on' === $data['expired_transients'],
				'transients'         => isset( $data['transients'] ) && 'on' === $data['transients'],
			);

			$options['db_cleanups'] = false;
			if ( isset( $data['scheduled_cleanup'] ) && 'on' === $data['scheduled_cleanup'] ) {
				$options['db_cleanups'] = true;
			}

			$options['db_frequency'] = absint( $data['cleanup_frequency'] );
			$options['db_tables']    = $tables;
		}

		// Lazy load tab.
		if ( 'advanced-lazy-settings' === $form ) {
			$options['lazy_load'] = array(
				'enabled'   => isset( $data['lazy_load'] ) && 'on' === $data['lazy_load'],
				'method'    => isset( $data['method'] ) ? $data['method'] : 'click',
				'button'    => isset( $data['button'] ) ? $data['button'] : '',
				'threshold' => isset( $data['threshold'] ) ? $data['threshold'] : 0,
			);
		}

		$adv_module->update_options( $options );
		wp_send_json_success( array( 'success' => true ) );
	}

	/**
	 * Purge page cache preloader.
	 *
	 * @since 2.7.0
	 */
	public function advanced_purge_cache() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			die();
		}

		$preloader = new Preload();
		$preloader->clear_all_queue();

		wp_send_json_success();
	}

	/**
	 * Purge asset optimization groups.
	 *
	 * @since 2.7.0
	 */
	public function advanced_purge_minify() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			die();
		}

		Utils::get_module( 'minify' )->clear_files();

		wp_send_json_success();
	}

	/**
	 * Purge asset optimization orphaned data.
	 *
	 * @since 2.7.0
	 */
	public function advanced_purge_orphaned() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			die();
		}

		$rows = filter_input( INPUT_POST, 'rows', FILTER_SANITIZE_NUMBER_INT );

		Utils::get_module( 'advanced' )->purge_orphaned_step( (int) $rows );

		$load = sys_getloadavg();
		wp_send_json_success(
			array(
				'highCPU' => $load[0] > 0.5,
			)
		);
	}

	/**
	 * *************************
	 * LOGGER MODULE AJAX ACTIONS
	 ***************************/

	/**
	 * Clear logs.
	 *
	 * @since 1.9.2
	 */
	public function logger_clear() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['module'] ) ) { // Input var okay.
			die();
		}

		$slug = sanitize_text_field( wp_unslash( $_POST['module'] ) ); // Input var ok.

		$module = Utils::get_module( $slug );

		if ( ! $module ) {
			wp_send_json_success(
				array(
					'success' => false,
					'message' => __( 'Module not found', 'wphb' ),
				)
			);
		}

		$status = WP_Hummingbird::get_instance()->core->logger->clear( $slug );

		if ( ! $status ) {
			wp_send_json_success(
				array(
					'success' => false,
					'message' => __( 'Log file not found or empty', 'wphb' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'success' => true,
				'message' => __( 'Log file purged', 'wphb' ),
			)
		);
	}

	/**
	 * *************************
	 * HUMMINGBIRD ADMIN SETTINGS AJAX ACTIONS
	 ***************************/

	/**
	 * Save Admin settings.
	 *
	 * @since 1.9.3
	 */
	public function admin_settings_save_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) || ! isset( $_POST['form_data'] ) ) { // Input var okay.
			die();
		}
		parse_str( sanitize_text_field( wp_unslash( $_POST['form_data'] ) ), $data ); // Input var ok.

		$settings = Settings::get_settings( 'settings' );

		foreach ( $data as $setting => $value ) {
			if ( ! isset( $settings[ $setting ] ) ) {
				continue;
			}

			if ( 'control' === $setting ) {
				$settings[ $setting ] = $this->process_cache_control_settings( $data );
			} else {
				$settings[ $setting ] = (bool) $value;
			}
		}

		Settings::update_settings( $settings, 'settings' );

		wp_send_json_success();
	}

	/**
	 * Process cache control settings.
	 *
	 * @since 3.0.1
	 *
	 * @param array $data  Form data.
	 */
	private function process_cache_control_settings( $data ) {
		if ( false === (bool) $data['control'] ) {
			return false;
		}

		if ( isset( $data['type'] ) && 'all' === $data['type'] ) {
			return true;
		}

		$available_cache_types = Utils::get_active_cache_modules();

		$types = array();
		foreach ( $available_cache_types as $type => $name ) {
			if ( ! isset( $data[ $type ] ) ) {
				continue;
			}

			$types[] = $type;
		}

		return $types;
	}

	/**
	 * Reset plugin settings.
	 *
	 * @since 2.0.0
	 */
	public function reset_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		wp_send_json_success();
	}

	/**
	 * Toggle tracking from quick setup modal.
	 *
	 * @since 2.5.0
	 */
	public function toggle_tracking() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$status = filter_input( INPUT_POST, 'status', FILTER_VALIDATE_BOOLEAN );

		Settings::update_setting( 'tracking', $status, 'settings' );

		wp_send_json_success();
	}

	/**
	 * Deletes the flag to show upgrade summary.
	 */
	public function hide_upgrade_summary() {
		delete_site_option( 'wphb_show_upgrade_summary' );
		wp_send_json_success();
	}

	/**
	 * Export settings.
	 *
	 * @since 2.6.0
	 */
	public function admin_settings_export_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		$data = array(
			'version'         => WPHB_VERSION,
			'plugins'         => get_option( 'active_plugins' ),
			'network_plugins' => get_site_option( 'active_sitewide_plugins' ),
			'theme'           => get_stylesheet(),
			'settings'        => array(),
		);

		// Right now we are exporting only asset optimization settings.
		$minify_options = Settings::get_settings( 'minify' );

		$data['settings']['minify'] = $minify_options;

		$file_name = 'hummingbird-asset-optimization-settings.json';
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename=' . $file_name );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' ); // HTTP 1.1.
		header( 'Pragma: no-cache' ); // HTTP 1.0.
		header( 'Expires: 0' ); // Proxies.
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit();
	}

	/**
	 * Import settings.
	 *
	 * @since 2.6.0
	 */
	public function admin_settings_import_settings() {
		check_ajax_referer( 'wphb-fetch', 'nonce' );

		if ( ! current_user_can( Utils::get_admin_capability() ) ) {
			die();
		}

		if ( ! isset( $_FILES['settings_json_file'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Import failed - No settings file has been submitted.', 'wphb' ),
				)
			);
		}

		$file = $_FILES['settings_json_file'];
		if ( ! isset( $file['error'] ) || is_array( $file['error'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Import failed - Something went wrong on uploading settings file.', 'wphb' ),
				)
			);
		}

		if ( $file['size'] > 1000000 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Import failed - You selected wrong settings file.', 'wphb' ),
				)
			);
		}

		if ( 'application/json' !== $file['type'] ) {
			wp_send_json_error(
				array(
					'message' => __( 'Import failed - You selected wrong settings file.', 'wphb' ),
				)
			);
		}

		$content = file_get_contents( $file['tmp_name'] );
		if ( empty( $content ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Import failed - Settings data is empty.', 'wphb' ),
				)
			);
		}

		$data = json_decode( $content, true );
		if ( ! isset( $data['settings']['minify'] ) || ! is_array( $data['settings']['minify'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Import failed - Asset optimization settings data not found.', 'wphb' ),
				)
			);
		}
		// Right now we are importing only asset optimization settings.
		Settings::update_settings( $data['settings']['minify'], 'minify' );

		wp_send_json_success(
			array(
				'message' => __( 'Settings imported and configured successfully.', 'wphb' ),
			)
		);
	}

	/**
	 * Save current settings as a config.
	 *
	 * @since 3.0.1
	 */
	public function save_config() {
		check_ajax_referer( 'wphb-fetch' );

		$capability = is_multisite() ? 'manage_network' : 'manage_options';
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( null, 403 );
		}

		$configs = new Configs();
		wp_send_json_success( $configs->get_config_from_current() );
	}

	/**
	 * Upload config from file.
	 *
	 * @since 3.0.1
	 */
	public function upload_config() {
		check_ajax_referer( 'wphb-fetch' );

		$capability = is_multisite() ? 'manage_network' : 'manage_options';
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( null, 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file = isset( $_FILES['file'] ) ? wp_unslash( $_FILES['file'] ) : false;

		$configs    = new Configs();
		$new_config = $configs->save_uploaded_config( $file );

		if ( ! is_wp_error( $new_config ) ) {
			wp_send_json_success( $new_config );
		}

		wp_send_json_error(
			array( 'error_msg' => $new_config->get_error_message() )
		);
	}

	/**
	 * Apply selected config.
	 *
	 * @since 3.0.1
	 */
	public function apply_config() {
		check_ajax_referer( 'wphb-fetch' );

		$capability = is_multisite() ? 'manage_network' : 'manage_options';
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( null, 403 );
		}

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( ! $id ) {
			// Abort if no config ID was given.
			wp_send_json_error(
				array( 'error_msg' => esc_html__( 'Missing config ID', 'wphb' ) )
			);
		}

		$configs  = new Configs();
		$response = $configs->apply_config_by_id( $id );

		if ( ! is_wp_error( $response ) ) {
			wp_send_json_success();
		}

		wp_send_json_error(
			array( 'error_msg' => esc_html( $response->get_error_message() ) )
		);
	}
}
