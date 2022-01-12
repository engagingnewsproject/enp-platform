<?php
/**
 * Abstract module for reports modules.
 *
 * Supports Performance reports and Uptime reports.
 * Also used for sending out email reports after performance scans
 *
 * @since 1.9.4
 * @package Hummingbird\Core\Pro\Modules
 */

namespace Hummingbird\Core\Pro\Modules;

use DateTime;
use DateTimeZone;
use Exception;
use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Reports
 */
abstract class Reports extends Module {

	/**
	 * Module slug. It's used in the database calls. Accepted values: performance, uptime.
	 *
	 * @var string $module
	 */
	protected static $module;

	/**
	 * Initialize the module
	 *
	 * @since 1.9.4
	 */
	public function init() {
		add_action( 'wphb_activate', array( $this, 'maybe_enable_cron_jobs' ) );
		add_action( 'wphb_admin_do_meta_boxes_wphb-notifications', array( $this, 'maybe_enable_cron_jobs' ) );

		// Process report cron task.
		add_action( 'wphb_' . $this::$module . '_report', array( $this, 'process_report' ) );
	}

	/**
	 * Execute the module actions.
	 */
	public function run() {}

	/**
	 * Implement abstract parent method for clearing cache.
	 */
	public function clear_cache() {}

	/**
	 * Function to process cron report task.
	 */
	abstract protected function process_report();

	/**
	 * Get email subject.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	abstract protected function get_subject();

	/**
	 * Get report params for email.
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	abstract protected function get_params();

	/**
	 * Should send out report.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	protected function should_continue() {
		return true;
	}

	/**
	 * Enable cron task. Triggered during plugin activation.
	 *
	 * @since 1.9.4
	 */
	public function maybe_enable_cron_jobs() {
		if ( ! Utils::is_member() ) {
			return;
		}

		$options = Settings::get_setting( 'reports', $this::$module );

		// Not enabled - do nothing.
		if ( ! isset( $options['enabled'] ) || ! $options['enabled'] ) {
			return;
		}

		// Notifications enabled, but not scheduled - reschedule.
		$timestamp = wp_next_scheduled( 'wphb_' . $this::$module . '_report' );
		if ( false === $timestamp ) {
			$next_event = self::get_scheduled_time( $this::$module );
			if ( $next_event ) {
				wp_schedule_single_event( $next_event, 'wphb_' . $this::$module . '_report' );
			}
		}
	}

	/**
	 * Get the schedule time for a scan.
	 *
	 * @since 1.4.5
	 *
	 * @param string $module      Module slug.
	 * @param bool   $clear_cron  Force clear scanning cron.
	 *
	 * @return false|int
	 */
	public static function get_scheduled_time( $module, $clear_cron = true ) {
		if ( $clear_cron ) {
			wp_clear_scheduled_hook( "wphb_{$module}_report" );
		}

		$settings = Settings::get_setting( 'reports', $module );
		$time_str = Utils::pro()->module( 'notifications' )->get_reports_time_string( $settings );

		return self::local_to_utc( $time_str );
	}

	/**
	 * Local time to UTC.
	 *
	 * @since 1.4.5
	 *
	 * @param string $time  Time string.
	 *
	 * @return false|int
	 */
	public static function local_to_utc( $time ) {
		$tz = get_option( 'timezone_string' );
		if ( ! $tz ) {
			$gmt_offset = get_option( 'gmt_offset' );
			if ( 0 === $gmt_offset ) {
				return strtotime( $time );
			}
			$tz = self::get_timezone_string( $gmt_offset );
		}

		if ( ! $tz ) {
			$tz = 'UTC';
		}
		$timezone = new DateTimeZone( $tz );
		try {
			$time = new DateTime( $time, $timezone );
			return $time->getTimestamp();
		} catch ( Exception $e ) {
			error_log( '[' . current_time( 'mysql' ) . '] - Error in local_to_utc(). Error: ' . $e->getMessage() );
		}

		return false;
	}

	/**
	 * Get time zone string.
	 *
	 * @since  1.4.5
	 *
	 * @param  string $timezone  Time zone.
	 *
	 * @return false|string
	 */
	private static function get_timezone_string( $timezone ) {
		$timezone = explode( '.', $timezone );
		if ( isset( $timezone[1] ) ) {
			$timezone[1] = 30;
		} else {
			$timezone[1] = '00';
		}
		$offset                  = implode( ':', $timezone );
		list( $hours, $minutes ) = explode( ':', $offset );
		$seconds                 = $hours * 60 * 60 + $minutes * 60;
		$tz                      = timezone_name_from_abbr( '', $seconds, 1 );
		if ( false === $tz ) {
			$tz = timezone_name_from_abbr( '', $seconds, 0 );
		}

		return $tz;
	}

	/**
	 * Send out an email report.
	 *
	 * @since 1.4.5
	 *
	 * @param mixed $last_report  Last report data.
	 */
	protected function send_email_report( $last_report ) {
		if ( ! $this->should_continue() ) {
			return;
		}

		$options = Settings::get_setting( 'reports', $this::$module );

		if ( empty( $options['recipients'] ) ) {
			return;
		}

		foreach ( $options['recipients'] as $recipient ) {
			$params = array(
				'REPORT_TYPE' => $this::$module,
				'USER_NAME'   => $recipient['name'],
				'SITE_URL'    => wp_parse_url( site_url(), PHP_URL_HOST ),
				'SITE_NAME'   => get_bloginfo( 'name' ),
			);
			$params = array_merge( $params, $this->get_params() );

			$email_content = self::issues_list_html( $last_report, $params );

			// Change nl to br.
			$email_content  = stripslashes( $email_content );
			$no_reply_email = 'noreply@' . wp_parse_url( get_site_url(), PHP_URL_HOST );
			$headers        = array(
				'From: Hummingbird <' . $no_reply_email . '>',
				'Content-Type: text/html; charset=UTF-8',
			);

			wp_mail( $recipient['email'], $this->get_subject(), $email_content, $headers );
		}
	}

	/**
	 * Build issues html table.
	 *
	 * @access private
	 * @param  mixed $last_test  Latest test data.
	 * @param  array $params     Additional data for report.
	 * @return string            HTML for email.
	 * @since  1.4.5
	 */
	protected static function issues_list_html( $last_test, $params ) {
		ob_start();
		self::load_template( 'index', compact( 'last_test', 'params' ) );
		return ob_get_clean();
	}

	/**
	 * Try to load a single reporting template.
	 *
	 * @param string $template  Template name. It should match the filename without extension.
	 * @param array  $args      Variables to pass to the templates.
	 */
	public static function load_template( $template, $args = array() ) {
		$dirs = apply_filters(
			'wphb_reporting_templates_folders',
			array(
				'stylesheet' => get_stylesheet_directory() . '/wphb/',
				'template'   => get_template_directory() . '/wphb/',
				'plugin'     => WPHB_DIR_PATH . 'core/pro/modules/reporting/templates/',
			)
		);

		foreach ( (array) $dirs as $dir ) {
			$file = trailingslashit( $dir ) . "$template.php";
			if ( is_readable( $file ) ) {
				extract( $args );
				include $file;
				break;
			}
		}
	}

}