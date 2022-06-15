<?php
/**
 * Uptime module.
 *
 * @package Hummingbird\Core\Modules
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Module;
use Hummingbird\Core\Traits\Module as ModuleContract;
use Hummingbird\Core\Utils;
use WP_Error;
use WPMUDEV_Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Uptime
 */
class Uptime extends Module {

	use ModuleContract;

	/**
	 * Initialize module.
	 */
	public function init() {
		add_filter( 'wp_hummingbird_is_active_module_uptime', array( $this, 'module_status' ) );
	}

	/**
	 * Implement abstract parent method for clearing cache.
	 *
	 * @since 1.7.1
	 */
	public function clear_cache() {
		delete_site_transient( 'wphb-uptime-last-report' );
	}

	/**
	 * Get last report.
	 *
	 * @since 1.7.1 Removed static property.
	 * @param string $time   Report period.
	 * @param bool   $force  Force refresh.
	 *
	 * @return bool|WP_Error
	 */
	public function get_last_report( $time = 'week', $force = false ) {
		if ( ! $this->has_access() ) {
			return new WP_Error( 'uptime-membership', __( 'You need to be a WPMU DEV Member', 'wphb' ) );
		}

		$current_reports = get_site_transient( 'wphb-uptime-last-report' );
		if ( ! isset( $current_reports[ $time ] ) || $force ) {
			$current_reports = $this->refresh_report( $time );
		}

		if ( ! isset( $current_reports[ $time ] ) ) {
			return false;
		}

		return $current_reports[ $time ];
	}

	/**
	 * Get the latest report from server
	 *
	 * @since 1.7.1 Removed static property.
	 * @since 1.8.1 Access changed to private. Added $current_reports param.
	 *
	 * @access private
	 *
	 * @param string     $time             Report period.
	 * @param bool|array $current_reports  Current reports.
	 *
	 * @return array|bool
	 */
	private function refresh_report( $time = 'day', $current_reports = false ) {
		$results = Utils::get_api()->uptime->check( $time );

		if ( is_wp_error( $results ) && 412 === $results->get_error_code() ) {
			// Uptime has been deactivated.
			$this->disable_locally();
			delete_site_transient( 'wphb-uptime-last-report' );
			return false;
		}

		if ( ! $current_reports ) {
			$current_reports = array();
		}

		$current_reports[ $time ] = $results;
		// Save for 2 minutes.
		set_site_transient( 'wphb-uptime-last-report', $current_reports, 2 * MINUTE_IN_SECONDS );

		return $current_reports;
	}

	/**
	 * Check if Uptime is remotely enabled
	 *
	 * @return bool
	 */
	public static function is_remotely_enabled() {
		if ( ! Utils::get_module( 'uptime' )->has_access() ) {
			return false;
		}

		$cached = get_site_transient( 'wphb-uptime-remotely-enabled' );
		if ( 'yes' === $cached ) {
			return true;
		} elseif ( 'no' === $cached ) {
			return false;
		}

		$api    = Utils::get_api();
		$result = $api->uptime->is_enabled();
		// Save for 5 minutes.
		set_site_transient( 'wphb-uptime-remotely-enabled', $result ? 'yes' : 'no', 5 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Enable Uptime local and remotely
	 *
	 * @since 1.7.1 Remove static property
	 */
	public function enable() {
		$this->clear_cache();
		$this->enable_locally();

		delete_site_transient( 'wphb-uptime-remotely-enabled' );

		return Utils::get_api()->uptime->enable();
	}

	/**
	 * Disable Uptime local and remotely
	 *
	 * @since 1.7.1 Removed static property
	 */
	public function disable() {
		$this->clear_cache();
		$this->disable_locally();

		delete_site_transient( 'wphb-uptime-remotely-enabled' );

		return Utils::get_api()->uptime->disable();
	}

	/**
	 * Enable locally.
	 */
	public function enable_locally() {
		$options            = $this->get_options();
		$options['enabled'] = true;
		$this->update_options( $options );
		// Save for 3 minutes.
		set_site_transient( 'wphb-uptime-remotely-enabled', 'yes', 3 * MINUTE_IN_SECONDS );
	}

	/**
	 * Disable locally.
	 */
	public function disable_locally() {
		$options            = $this->get_options();
		$options['enabled'] = false;

		// Disable reports and notifications.
		$options['notifications']['enabled'] = false;
		$options['reports']['enabled']       = false;

		// Clean all cron.
		wp_clear_scheduled_hook( 'wphb_uptime_report' );

		$this->update_options( $options );
		// Save for 3 minutes.
		set_site_transient( 'wphb-uptime-remotely-enabled', 'no', 3 * MINUTE_IN_SECONDS );
	}

	/**
	 * Get module status.
	 *
	 * @param bool $current  Current status.
	 *
	 * @return bool
	 */
	public function module_status( $current ) {
		$options = $this->get_options();
		if ( ! $options['enabled'] ) {
			return false;
		}

		return $current;
	}

	/**
	 * Check if Uptime is accessible.
	 *
	 * Check if currently logged in member has access to Uptime feature using the membership data.
	 *
	 * @since 3.3.1
	 *
	 * @return bool
	 */
	public function has_access() {
		// Not a valid membership.
		if ( ! Utils::has_access_to_service() ) {
			return false;
		}

		// Helper function exist from Dash v4.11.9, use it.
		if ( method_exists( 'WPMUDEV_Dashboard_Api', 'has_access' ) ) {
			return WPMUDEV_Dashboard::$api->has_access( 'performance-uptime-monitor' );
		}

		// Check if required method exist.
		if ( ! method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_data' ) ) {
			return false;
		}

		// Get membership data.
		$data = WPMUDEV_Dashboard::$api->get_membership_data();

		// Get available features.
		$features = isset( $data['membership_access'] ) ? $data['membership_access'] : array();

		// If true, full access.
		if ( true === $features ) {
			return true;
		}

		// Check if uptime is available.
		return is_array( $features ) && in_array( 'performance-uptime-monitor', $features, true );
	}

}