<?php
/**
 * Class Reports_Performance is used for cron functionality.
 * Only for premium members.
 *
 * @since 1.5.0
 * @package Hummingbird\Core\Pro\Modules
 */

namespace Hummingbird\Core\Pro\Modules;

use Hummingbird\Core\Modules\Performance;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Reports_Performance extends Reports
 */
class Reports_Performance extends Reports {

	/**
	 * Module slug.
	 *
	 * @since 1.9.4
	 *
	 * @var string $module
	 */
	protected static $module = 'performance';

	/**
	 * Maybe skip report.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public function should_continue() {
		return ! Performance::is_doing_report();
	}

	/**
	 * Set report email subject.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_subject() {
		return sprintf( /* translators: %s: Url for site */
			__( "Here's your latest performance test results for %s", 'wphb' ),
			site_url()
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
			'NOTIFICATIONS_URL' => admin_url( 'admin.php?page=wphb-notifications' ),
			'FULL_REPORT_URL'   => admin_url( 'admin.php?page=wphb-performance' ),
			'SITE_MANAGE_URL'   => admin_url( 'admin.php?page=wphb' ),
			'DEVICE'            => $options['type'], // Can be: desktop, mobile, both.
			'SHOW_METRICS'      => $options['metrics'],
			'SHOW_AUDITS'       => $options['audits'],
			'SHOW_HISTORIC'     => $options['historic'],
		);
	}

	/**
	 * Ajax action for processing a scan on page.
	 *
	 * @since 1.4.5
	 */
	public function process_report() {
		// Clean all cron.
		wp_clear_scheduled_hook( 'wphb_performance_report' );

		if ( ! Utils::is_member() ) {
			return;
		}

		$options = Settings::get_settings( 'performance' );

		// Don't do any reports if they are not set in the options.
		if ( ! $options['reports']['enabled'] ) {
			return;
		}

		$limit = absint( get_site_transient( 'wphb_cron_limit' ) );

		// Refresh the report and get the data.
		Performance::refresh_report();
		$last_report = Performance::get_last_report();

		// Time since last report.
		$time_difference = 999999;
		if ( isset( $last_report->data ) && ! is_wp_error( $last_report ) ) {
			$time_difference = time() - (int) $last_report->data->time;
		}

		// If no report is present or report is outdated, get new data.
		if ( ( ! $last_report || $time_difference > 300 ) && $limit < 3 ) {
			// First run. Init new report scan.
			if ( 0 === $limit ) {
				Utils::get_module( 'performance' )->init_scan();
			}

			// Update cron limit.
			set_site_transient( 'wphb_cron_limit', ++$limit, 3600 );
			// Reschedule in 1 minute to collect results.
			wp_schedule_single_event( strtotime( '+1 minutes' ), 'wphb_performance_report' );
			return;
		}

		// Failed to fetch results in 3 attempts or fewer, cancel the cron.
		if ( 3 === $limit ) {
			delete_site_transient( 'wphb_cron_limit' );
		}

		// Check to see it the email has been sent already.
		$last_sent_report = isset( $options['reports']['last_sent'] ) ? (int) $options['reports']['last_sent'] : 0;
		$to_utc           = (int) parent::get_scheduled_time( self::$module, false );

		// Schedule next test (have at least an hour advance).
		if ( $time_difference < 300 && isset( $last_report ) && ( $to_utc - $last_sent_report ) > 3600 ) {
			// Send the report.
			$this->send_email_report( $last_report->data );
			// Store the last send time.
			$options['reports']['last_sent'] = time();
			Settings::update_settings( $options, 'performance' );
			delete_site_transient( 'wphb_cron_limit' );
		}

		// Reschedule.
		$next_scan_time = parent::get_scheduled_time( self::$module );
		if ( $next_scan_time ) {
			wp_schedule_single_event( $next_scan_time, 'wphb_performance_report' );
		}
	}

}