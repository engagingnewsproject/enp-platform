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
		$this->filter( 'rank_math/analytics/summary', 'get_adsense_summary' );
		$this->filter( 'rank_math/analytics/summary', 'get_pageviews_summary' );
		$this->filter( 'rank_math/analytics/get_widget', 'get_pageviews_summary' );
		$this->filter( 'rank_math/analytics/posts_summary', 'get_posts_summary' );
		$this->filter( 'rank_math/analytics/analytics_summary_graph', 'get_analytics_summary_graph', 10, 2 );
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
			'difference' => $pageviews - $old_pageviews,
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
		$earnings = DB::adsense()
			->selectSum( 'earnings', 'earnings' )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->getVar();

		$old_earnings = DB::adsense()
			->selectSum( 'earnings', 'earnings' )
			->whereBetween( 'created', [ Stats::get()->compare_start_date, Stats::get()->compare_end_date ] )
			->getVar();

		$earnings     = ! empty( $earnings ) ? $earnings : 0;
		$old_earnings = ! empty( $old_earnings ) ? $old_earnings : 0;

		$stats->adsense = [
			'total'      => $earnings,
			'previous'   => $old_earnings,
			'difference' => $earnings - $old_earnings,
		];

		return $stats;
	}

	/**
	 * Get graph data.
	 *
	 * @param  object $data      Graph data.
	 * @param  array  $intervals Date intervals.
	 * @return array
	 */
	public function get_analytics_summary_graph( $data, $intervals ) {
		global $wpdb;

		$interval = Stats::get()->get_sql_range( 'created' );

		$data->traffic = DB::traffic()
			->select( 'DATE_FORMAT( created,\'%Y-%m-%d\') as date' )
			->selectSum( 'pageviews', 'pageviews' )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->groupBy( $interval )
			->orderBy( 'created', 'ASC' )
			->get();

		$data->adsense = $this->get_adsense_graph();

		// Convert types.
		$data->traffic = array_map( [ Stats::get(), 'normalize_graph_rows' ], $data->traffic );
		$data->adsense = array_map( [ Stats::get(), 'normalize_graph_rows' ], $data->adsense );

		// Merge for performance.
		$data->merged = Stats::get()->get_merge_data_graph( $data->traffic, $data->merged, $intervals['map'] );
		$data->merged = Stats::get()->get_merge_data_graph( $data->adsense, $data->merged, $intervals['map'] );

		return $data;
	}

	/**
	 * Get adsense graph data.
	 *
	 * @return array
	 */
	public function get_adsense_graph() {
		$interval = Stats::get()->get_sql_range( 'created' );
		return DB::adsense()
			->select( 'DATE_FORMAT( created,\'%Y-%m-%d\') as date' )
			->selectSum( 'earnings', 'earnings' )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->groupBy( $interval )
			->orderBy( 'created', 'ASC' )
			->get();
	}
}
