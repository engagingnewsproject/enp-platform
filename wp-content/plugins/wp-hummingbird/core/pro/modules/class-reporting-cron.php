<?php
/**
 * Class Reporting_Cron is used for cron functionality.
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
 * Class Reporting_Cron extends Reports
 */
class Reporting_Cron extends Reports {

	/**
	 * Module slug.
	 *
	 * @since 1.9.4
	 *
	 * @var string $module
	 */
	protected static $module = 'performance';

	/**
	 * Initialize the module
	 *
	 * @since 1.5.0
	 */
	public function init() {
		parent::init();
		add_action( 'wphb_init_performance_scan', array( $this, 'on_init_performance_scan' ) );
	}

	/**
	 * Triggered when a performance scan is initialized
	 */
	public function on_init_performance_scan() {
		if ( ! Utils::is_member() ) {
			return;
		}

		$reports = Settings::get_setting( 'reports', 'performance' );
		// Do not continue if reports are not enabled.
		if ( ! $reports['enabled'] ) {
			return;
		}

		// Schedule first scan.
		if ( ! wp_next_scheduled( 'wphb_performance_report' ) ) {
			wp_schedule_single_event( parent::get_scheduled_time( self::$module ), 'wphb_performance_report' );
		}
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
		} else {
			// Failed to fetch results in 3 attempts or less, cancel the cron.
			if ( 3 === $limit ) {
				delete_site_transient( 'wphb_cron_limit' );
			}

			// Check to see it the email has been sent already.
			$last_sent_report = isset( $options['reports']['last_sent'] ) ? (int) $options['reports']['last_sent'] : 0;
			$to_utc           = (int) parent::get_scheduled_time( self::$module, false );

			// Schedule next test.
			if ( $time_difference < 300 && isset( $last_report ) && ( $to_utc - time() - $last_sent_report ) > 0 ) {
				// Get the recipient list.
				$recipients = $options['reports']['recipients'];
				// Send the report.
				$this->send_email_report( $last_report->data, $recipients );
				// Store the last send time.
				$options['reports']['last_sent'] = time();
				Settings::update_settings( $options, 'performance' );
				delete_site_transient( 'wphb_cron_limit' );
			}

			// Reschedule.
			$next_scan_time = parent::get_scheduled_time( self::$module );
			wp_schedule_single_event( $next_scan_time, 'wphb_performance_report' );
		}
	}

	/**
	 * Send out an email report.
	 *
	 * @since 1.4.5
	 *
	 * @param mixed $last_report  Last report data.
	 * @param array $recipients   List of recipients.
	 */
	public function send_email_report( $last_report, $recipients = array() ) {
		if ( Performance::is_doing_report() ) {
			return;
		}

		if ( empty( $recipients ) ) {
			return;
		}

		$options = Settings::get_setting( 'reports', 'performance' );

		foreach ( $recipients as $recipient ) {
			// Prepare the parameters.
			$email = $recipient['email'];
			/* translators: %s: Url for site */
			$subject       = sprintf( __( "Here's your latest performance test results for %s", 'wphb' ), network_site_url() );
			$params        = array(
				'REPORT_TYPE'     => 'performance',
				'USER_NAME'       => $recipient['name'],
				'SCAN_PAGE_LINK'  => network_admin_url( 'admin.php?page=wphb-performance' ),
				'SITE_MANAGE_URL' => network_site_url( 'wp-admin/admin.php?page=wphb' ),
				'SITE_URL'        => wp_parse_url( network_site_url(), PHP_URL_HOST ),
				'SITE_NAME'       => get_bloginfo( 'name' ),
				'DEVICE'          => $options['type'], // Can be: desktop, mobile, both.
				'SHOW_METRICS'    => $options['metrics'],
				'SHOW_AUDITS'     => $options['audits'],
				'SHOW_HISTORIC'   => $options['historic'],
			);
			$email_content = parent::issues_list_html( $last_report, $params );
			// Change nl to br.
			$email_content  = stripslashes( $email_content );
			$no_reply_email = 'noreply@' . wp_parse_url( get_site_url(), PHP_URL_HOST );
			$headers        = array(
				'From: Hummingbird <' . $no_reply_email . '>',
				'Content-Type: text/html; charset=UTF-8',
			);

			wp_mail( $email, $subject, $email_content, $headers );
		}
	}

}