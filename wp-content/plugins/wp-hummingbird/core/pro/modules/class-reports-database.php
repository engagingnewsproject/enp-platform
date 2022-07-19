<?php
/**
 * Class Reports_Database
 *
 * Only for premium members.
 *
 * @since 1.8
 * @package Hummingbird\Core\Pro\Modules
 */

namespace Hummingbird\Core\Pro\Modules;

use Hummingbird\Core\Modules\Advanced;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Reports_Database extends Reports
 */
class Reports_Database extends Reports {

	/**
	 * Module slug.
	 *
	 * @since 3.2.0
	 *
	 * @var string $module
	 */
	protected static $module = 'database';

	/**
	 * Set report email subject.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function get_subject() {
		return sprintf( /* translators: %s: Url for site */
			__( "Here's your latest database cleanup report for %s", 'wphb' ),
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
		return array(
			'FIELDS'          => Advanced::get_db_fields(),
			'SITE_MANAGE_URL' => admin_url( 'admin.php?page=wphb' ),
		);
	}

	/**
	 * Process cron task to clean the database.
	 *
	 * @since 3.2.0
	 */
	public function process_report() {
		wp_clear_scheduled_hook( 'wphb_database_report' );

		if ( ! Utils::is_member() ) {
			return;
		}

		$options = Settings::get_setting( 'reports', 'database' );

		// Don't do any reports if they are not set in the options.
		if ( ! $options['enabled'] || ! isset( $options['tables'] ) ) {
			return;
		}

		$items = array();
		foreach ( $options['tables'] as $type => $value ) {
			if ( false === (bool) $value ) {
				continue;
			}

			$results = Utils::get_module( 'advanced' )->delete_db_data( $type );

			$items['left']  = $results['left'];
			$items[ $type ] = $results['items'];
		}

		// Check to see it the email has been sent already.
		$last_sent_report = isset( $options['last_sent'] ) ? (int) $options['last_sent'] : 0;
		$next_send_time   = (int) parent::get_scheduled_time( self::$module, false );

		// Schedule next test.
		if ( ! empty( $items ) && ( time() > $next_send_time ) && ( $next_send_time - $last_sent_report ) > 3600 ) {
			// Send the report.
			$this->send_email_report( $items );

			// Store the last send time.
			$options['last_sent'] = time();

			Settings::update_setting( 'reports', $options, 'database' );
		}

		// Reschedule.
		wp_schedule_single_event( $next_send_time, 'wphb_database_report' );
	}

}