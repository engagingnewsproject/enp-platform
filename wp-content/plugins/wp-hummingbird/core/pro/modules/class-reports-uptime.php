<?php
/**
 * Uptime reports and notifications module: Reports_Uptime class
 *
 * Only for premium users.
 *
 * @since 1.9.3
 * @package Hummingbird\Core\Pro\Modules
 */

namespace Hummingbird\Core\Pro\Modules;

use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Reports_Uptime extends Reports
 */
class Reports_Uptime extends Reports {

	/**
	 * Module slug.
	 *
	 * @since 1.9.4
	 *
	 * @var string $module
	 */
	protected static $module = 'uptime';

	/**
	 * Set report email subject.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_subject() {
		return sprintf( /* translators: %s: Url for site */
			__( "Here's your latest uptime report for %s", 'wphb' ),
			network_site_url()
		);
	}

	/**
	 * Add required report parameters.
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	public function get_params() {
		$options = Settings::get_setting( 'reports', self::$module );

		return array(
			'FULL_REPORT_URL' => network_admin_url( 'admin.php?page=wphb-uptime' ),
			'SITE_MANAGE_URL' => network_site_url( 'wp-admin/admin.php?page=wphb' ),
			'SHOW_PING'       => isset( $options['show_ping'] ) ? $options['show_ping'] : true,
		);

	}

	/**
	 * Ajax action for processing uptime reports.
	 *
	 * @since 1.9.4
	 */
	public function process_report() {
		// Clean all cron.
		wp_clear_scheduled_hook( 'wphb_uptime_report' );

		if ( ! Utils::is_member() ) {
			return;
		}

		$options = Settings::get_setting( 'reports', 'uptime' );

		// Don't do any reports if they are not set in the options.
		if ( ! $options['enabled'] ) {
			return;
		}

		switch ( $options['frequency'] ) {
			case 1:
				$period = 'day';
				break;
			case 7:
			default:
				$period = 'week';
				break;
			case 30:
				$period = 'month';
				break;
		}

		// Refresh the report and get the data.
		$last_report = Utils::get_module( 'uptime' )->get_last_report( $period, true );

		// Check to see it the email has been sent already.
		$last_sent_report = isset( $options['last_sent'] ) ? (int) $options['last_sent'] : 0;
		$next_send_time   = (int) parent::get_scheduled_time( self::$module, false );

		// Schedule next test.
		if ( isset( $last_report ) && ! is_wp_error( $last_report ) && ( $next_send_time - $last_sent_report ) > 3600 ) {
			// Send the report.
			$this->send_email_report( $last_report );

			// Store the last send time.
			$options['last_sent'] = time();

			Settings::update_setting( 'reports', $options, 'uptime' );
		}

		// Reschedule.
		$next_scan_time = parent::get_scheduled_time( self::$module );
		if ( $next_scan_time ) {
			wp_schedule_single_event( $next_scan_time, 'wphb_uptime_report' );
		}
	}

}