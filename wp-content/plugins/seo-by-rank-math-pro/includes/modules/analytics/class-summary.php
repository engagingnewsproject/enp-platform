<?php
/**
 * The Analytics Module
 *
 * @since      2.0.0
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use MyThemeShop\Helpers\DB as DB_Helper;

use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;


defined( 'ABSPATH' ) || exit;

/**
 * Summary class.
 */
class Summary {

	use Hooker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( \RankMathPro\Google\Adsense::is_adsense_connected() ) {
			$this->filter( 'rank_math/analytics/summary', 'get_adsense_summary' );
		}
		if ( \RankMath\Google\Analytics::is_analytics_connected() ) {
			$this->filter( 'rank_math/analytics/summary', 'get_pageviews_summary' );
			$this->filter( 'rank_math/analytics/get_widget', 'get_pageviews_summary' );
		}
		$this->filter( 'rank_math/analytics/summary', 'get_clicks_summary' );
		$this->filter( 'rank_math/analytics/posts_summary', 'get_posts_summary' );
		$this->filter( 'rank_math/analytics/analytics_summary_graph', 'get_analytics_summary_graph', 10, 2 );
		$this->filter( 'rank_math/analytics/analytics_tables_info', 'get_analytics_tables_info' );
	}

	/**
	 * Get posts summary.
	 *
	 * @param  object $summary Posts summary.
	 * @return object
	 */
	public function get_posts_summary( $summary ) {
		$summary->pageviews = DB::traffic()
			->selectSum( 'pageviews', 'pageviews' )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->getVar();

		return $summary;
	}

	/**
	 * Get pageviews summary.
	 *
	 * @param  object $stats Stats holder.
	 * @return object
	 */
	public function get_pageviews_summary( $stats ) {
		$pageviews = DB::traffic()
			->selectSum( 'pageviews', 'pageviews' )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->getVar();

		$old_pageviews = DB::traffic()
			->selectSum( 'pageviews', 'pageviews' )
			->whereBetween( 'created', [ Stats::get()->compare_start_date, Stats::get()->compare_end_date ] )
			->getVar();

		$stats->pageviews = [
			'total'      => (int) $pageviews,
			'previous'   => (int) $old_pageviews,
			'difference' => (int) $pageviews - (int) $old_pageviews,
		];

		return $stats;
	}

	/**
	 * Get adsense summary.
	 *
	 * @param  object $stats Stats holder.
	 * @return object
	 */
	public function get_adsense_summary( $stats ) {
		$stats->adsense = [
			'total'      => 0,
			'previous'   => 0,
			'difference' => 0,
		];

		if ( DB_Helper::check_table_exists( 'rank_math_analytics_adsense' ) ) {
			$earnings = DB::adsense()
				->selectSum( 'earnings', 'earnings' )
				->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
				->getVar();

			$old_earnings = DB::adsense()
				->selectSum( 'earnings', 'earnings' )
				->whereBetween( 'created', [ Stats::get()->compare_start_date, Stats::get()->compare_end_date ] )
				->getVar();

			$stats->adsense = [
				'total'      => (int) $earnings,
				'previous'   => (int) $old_earnings,
				'difference' => (int) $earnings - (int) $old_earnings,
			];
		}

		return $stats;
	}

	/**
	 * Get analytics and adsense graph data.
	 *
	 * @param  object $data      Graph data.
	 * @param  array  $intervals Date intervals.
	 * @return array
	 */
	public function get_analytics_summary_graph( $data, $intervals ) {
		global $wpdb;

		if ( \RankMath\Google\Analytics::is_analytics_connected() ) {
			$data->traffic = $this->get_traffic_graph( $intervals );

			// Convert types.
			$data->traffic = array_map( [ Stats::get(), 'normalize_graph_rows' ], $data->traffic );

			// Merge for performance.
			$data->merged = Stats::get()->get_merge_data_graph( $data->traffic, $data->merged, $intervals['map'] );
		}

		if ( \RankMathPro\Google\Adsense::is_adsense_connected() ) {
			$data->adsense = $this->get_adsense_graph( $intervals );

			// Convert types.
			$data->adsense = array_map( [ Stats::get(), 'normalize_graph_rows' ], $data->adsense );

			// Merge for performance.
			$data->merged = Stats::get()->get_merge_data_graph( $data->adsense, $data->merged, $intervals['map'] );
		}

		return $data;
	}

	/**
	 * Get analytics graph data.
	 *
	 * @param  array $intervals Date intervals.
	 * @return array
	 */
	public function get_traffic_graph( $intervals ) {
		global $wpdb;

		$sql_daterange = Stats::get()->get_sql_date_intervals( $intervals );

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT DATE_FORMAT( created, '%%Y-%%m-%%d') as date, SUM(pageviews) as pageviews, {$sql_daterange}
			FROM {$wpdb->prefix}rank_math_analytics_ga
			WHERE created BETWEEN %s AND %s
			GROUP BY range_group",
			Stats::get()->start_date,
			Stats::get()->end_date
		);
		$traffic_data = $wpdb->get_results( $query );
		// phpcs:enable

		return $traffic_data;
	}

	/**
	 * Get adsense graph data.
	 *
	 * @param  array $intervals Date intervals.
	 * @return array
	 */
	public function get_adsense_graph( $intervals ) {
		global $wpdb;

		$adsense_data = [];

		if ( DB_Helper::check_table_exists( 'rank_math_analytics_adsense' ) ) {
			$sql_daterange = Stats::get()->get_sql_date_intervals( $intervals );

			// phpcs:disable
			$query = $wpdb->prepare(
				"SELECT DATE_FORMAT( created, '%%Y-%%m-%%d') as date, SUM(earnings) as earnings, {$sql_daterange}
				FROM {$wpdb->prefix}rank_math_analytics_adsense
				WHERE created BETWEEN %s AND %s
				GROUP BY range_group",
				Stats::get()->start_date,
				Stats::get()->end_date
			);
			$adsense_data = $wpdb->get_results( $query );
			// phpcs:enable
		}

		return $adsense_data;
	}

	/**
	 * Get clicks summary.
	 *
	 * @param  object $stats Stats holder.
	 * @return object
	 */
	public function get_clicks_summary( $stats ) {
		$clicks = DB::analytics()
			->selectSum( 'clicks', 'clicks' )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->getVar();

		$old_clicks = DB::analytics()
			->selectSum( 'clicks', 'clicks' )
			->whereBetween( 'created', [ Stats::get()->compare_start_date, Stats::get()->compare_end_date ] )
			->getVar();

		$stats->clicks = [
			'total'      => (int) $clicks,
			'previous'   => (int) $old_clicks,
			'difference' => $clicks - $old_clicks,
		];

		return $stats;
	}

	/**
	 * Get analytics tables info
	 *
	 * @param  array $data      Analytics tables info.
	 * @return array
	 */
	public function get_analytics_tables_info( $data ) {
		$pro_data = DB::info();

		$days = $data['days'] + $pro_data['days'];
		$rows = $data['rows'] + $pro_data['rows'];
		$size = $data['size'] + $pro_data['size'];

		$data = compact( 'days', 'rows', 'size' );

		return $data;
	}
}
