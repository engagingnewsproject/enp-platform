<?php
/**
 * Uptime reports and notifications module: Uptime_Reports class
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
 * Class Uptime_Reports extends Reports
 */
class Uptime_Reports extends Reports {

	/**
	 * Module slug.
	 *
	 * @since 1.9.4
	 *
	 * @var string $module
	 */
	protected static $module = 'uptime';

	/**
	 * Initialize the module
	 *
	 * @since 1.9.3
	 */
	public function init() {
		parent::init();

		// Default settings for Uptime notifications.
		add_filter( 'wp_hummingbird_default_options', array( $this, 'add_default_options' ) );
	}

	/**
	 * Add a set of default options to Hummingbird settings.
	 *
	 * @since 1.9.3
	 *
	 * @param array $options  List of default Hummingbird settings.
	 *
	 * @return array
	 */
	public function add_default_options( $options ) {
		$options['uptime']['notifications']['enabled']    = true;
		$options['uptime']['notifications']['threshold']  = 0;
		$options['uptime']['notifications']['recipients'] = array();

		return $options;
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
		if ( isset( $last_report ) && ! is_wp_error( $last_report ) && ( $next_send_time - time() - $last_sent_report ) > 0 ) {
			// Get the recipient list.
			$recipients = $options['recipients'];

			// Send the report.
			$this->send_email_report( $last_report, $recipients );

			// Store the last send time.
			$options['last_sent'] = time();

			Settings::update_setting( 'reports', $options, 'uptime' );
		}

		// Reschedule.
		$next_scan_time = parent::get_scheduled_time( self::$module );
		wp_schedule_single_event( $next_scan_time, 'wphb_uptime_report' );
	}

	/**
	 * Send out an email report.
	 *
	 * @since 1.9.4
	 *
	 * @param mixed $last_report  Last report data.
	 * @param array $recipients   List of recipients.
	 */
	public function send_email_report( $last_report, $recipients = array() ) {
		if ( empty( $recipients ) ) {
			return;
		}

		foreach ( $recipients as $recipient ) {
			// Prepare the parameters.
			$email = $recipient['email'];
			/* translators: %s: Url for site */
			$subject       = sprintf( __( "Here's your latest uptime report for %s", 'wphb' ), network_site_url() );
			$params        = array(
				'REPORT_TYPE'     => 'uptime',
				'USER_NAME'       => $recipient['name'],
				'SCAN_PAGE_LINK'  => network_admin_url( 'admin.php?page=wphb-uptime' ),
				'SITE_MANAGE_URL' => network_site_url( 'wp-admin/admin.php?page=wphb' ),
				'SITE_URL'        => wp_parse_url( network_site_url(), PHP_URL_HOST ),
				'SITE_NAME'       => get_bloginfo( 'name' ),
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