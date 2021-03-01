<?php
/**
 * Jobs.
 *
 * @since      1.0.54
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics\Workflow;

use Exception;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMathPro\Analytics\DB;
use RankMathPro\Google\Adsense;
use RankMathPro\Google\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Jobs class.
 */
class Jobs {

	use Hooker;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Jobs
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Jobs ) ) {
			$instance = new Jobs();
			$instance->hooks();
		}

		return $instance;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		// Daily Tasks.
		$this->action( 'rank_math/analytics/data_fetch', 'data_fetch' );

		// Data Fetcher.
		$this->action( 'rank_math/analytics/get_adsense_data', 'get_adsense_data' );
		$this->action( 'rank_math/analytics/get_analytics_data', 'get_analytics_data' );

		// Cache.
		$this->action( 'rank_math/analytics/clear_cache', 'clear_cache' );
		$this->action( 'rank_math/analytics/purge_cache', 'purge_cache' );
		$this->action( 'rank_math/analytics/delete_by_days', 'delete_by_days' );
		$this->action( 'rank_math/analytics/delete_data_log', 'delete_data_log' );
	}

	/**
	 * Perform these tasks daily.
	 */
	public function data_fetch() {
		$this->check_for_missing_dates( 'console' );
	}

	/**
	 * Get analytics data.
	 *
	 * @param string $date Date to fetch data for.
	 */
	public function get_analytics_data( $date ) {
		$rows = Analytics::get_analytics( $date, $date );
		if ( empty( $rows ) ) {
			return;
		}

		try {
			DB::add_analytics_bulk( $date, $rows );
		} catch ( Exception $e ) {} // phpcs:ignore
	}

	/**
	 * Get adsense and save it into database.
	 *
	 * @param string $date Date to fetch data for.
	 */
	public function get_adsense_data( $date ) {
		$rows = Adsense::get_adsense( $date, $date );
		if ( empty( $rows ) ) {
			return;
		}

		try {
			DB::add_adsense( $date, $rows );
		} catch ( Exception $e ) {} // phpcs:ignore
	}

	/**
	 * Clear cache.
	 */
	public function clear_cache() {
		global $wpdb;

		// Delete all useless data from ga.
		$wpdb->get_results( "DELETE FROM {$wpdb->prefix}rank_math_analytics_ga WHERE page NOT IN ( SELECT page from {$wpdb->prefix}rank_math_analytics_objects )" );
	}

	/**
	 * Purge cache.
	 *
	 * @param object $table Table insance.
	 */
	public function purge_cache( $table ) {
		$table->whereLike( 'option_name', 'losing_posts' )->delete();
		$table->whereLike( 'option_name', 'winning_posts' )->delete();
		$table->whereLike( 'option_name', 'losing_keywords' )->delete();
		$table->whereLike( 'option_name', 'winning_keywords' )->delete();
		$table->whereLike( 'option_name', 'tracked_keywords_summary' )->delete();
	}

	/**
	 * Purge cache.
	 *
	 * @param  int $days Decide whether to delete all or delete 90 days data.
	 */
	public function delete_by_days( $days ) {
		if ( -1 === $days ) {
			DB::traffic()->truncate();
			DB::adsense()->truncate();
		} else {
			$start = date_i18n( 'Y-m-d H:i:s', strtotime( '-1 days' ) );
			$end   = date_i18n( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );

			DB::traffic()->whereBetween( 'created', [ $end, $start ] )->delete();
			DB::adsense()->whereBetween( 'created', [ $end, $start ] )->delete();
		}
	}

	/**
	 * Delete record for comparison.
	 *
	 * @param string $start Start date.
	 */
	public function delete_data_log( $start ) {
		DB::traffic()->where( 'created', '<', $start )->delete();
		DB::adsense()->where( 'created', '<', $start )->delete();
	}

	/**
	 * Check for missing dates.
	 *
	 * @param string $action Action to perform.
	 */
	private function check_for_missing_dates( $action ) {
		$count = 1;
		$hook  = "get_{$action}_data";
		$start = Helper::get_midnight( time() + DAY_IN_SECONDS );

		for ( $current = 1; $current <= 15; $current++ ) {
			$date = date_i18n( 'Y-m-d', $start - ( DAY_IN_SECONDS * $current ) );
			if ( ! DB::date_exists( $date, $action ) ) {
				$count++;
				as_schedule_single_action(
					time() + ( 60 * ( $count / 2 ) ),
					'rank_math/analytics/' . $hook,
					[ $date ],
					'rank-math'
				);
			}
		}

		// Clear cache.
		if ( $count > 1 ) {
			as_schedule_single_action(
				time() + ( 60 * ( ( $count + 1 ) / 2 ) ),
				'rank_math/analytics/clear_cache',
				[],
				'rank-math'
			);
		}
	}
}
