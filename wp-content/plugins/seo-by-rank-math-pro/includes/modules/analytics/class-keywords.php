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

use WP_REST_Request;
use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Keywords class.
 */
class Keywords {

	use Hooker;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Keywords
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Keywords ) ) {
			$instance = new Keywords();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function setup() {
		$this->filter( 'rank_math/analytics/keywords', 'add_keyword_position_graph' );
		$this->filter( 'rank_math/analytics/keywords_overview', 'add_winning_losing_data' );
	}

	/**
	 * Add keyword position graph.
	 *
	 * @param  array $rows Rows.
	 * @return array
	 */
	public function add_keyword_position_graph( $rows ) {
		$history = $this->get_graph_data_for_keywords( \array_keys( $rows ) );
		$rows    = Stats::get()->set_query_position( $rows, $history );

		return $rows;
	}

	/**
	 * Add winning losing data.
	 *
	 * @param  array $data Data.
	 * @return array
	 */
	public function add_winning_losing_data( $data ) {
		$data['winningKeywords'] = $this->get_winning_keywords();
		$data['losingKeywords']  = $this->get_losing_keywords();

		return $data;
	}

	/**
	 * Add tack keyword.
	 *
	 * @param string $keyword Keyword.
	 */
	public function add_track_keyword( $keyword ) {
		DB::keywords()->insert(
			[
				'keyword'    => $keyword,
				'collection' => 'uncategorized',
				'is_active'  => true,
			],
			[ '%s', '%s', '%d' ]
		);

		delete_transient( Stats::get()->get_cache_key( 'tracked_keywords_summary', Stats::get()->days . 'days' ) );
	}

	/**
	 * Remove tack keyword.
	 *
	 * @param string $keyword Keyword.
	 */
	public function remove_track_keyword( $keyword ) {
		DB::keywords()->where( 'keyword', $keyword )
			->delete();

		delete_transient( Stats::get()->get_cache_key( 'tracked_keywords_summary', Stats::get()->days . 'days' ) );
	}

	/**
	 * Get keywords summary.
	 *
	 * @return object
	 */
	public function get_tracked_keywords_summary() {
		$summary = get_option(
			'rank_math_keyword_quota',
			[
				'taken'     => 0,
				'available' => 40,
			]
		);

		return $summary;
	}

	/**
	 * Get winning keywords.
	 *
	 * @return object
	 */
	public function get_tracked_winning_keywords() {
		return $this->get_tracked_keywords(
			[
				'limit' => 'LIMIT 5',
				'where' => 'WHERE COALESCE( ROUND( t1.position - t2.position, 0 ), 0 ) > 0',
			]
		);
	}

	/**
	 * Get losing keywords.
	 *
	 * @return object
	 */
	public function get_tracked_losing_keywords() {
		return $this->get_tracked_keywords(
			[
				'order' => 'ASC',
				'limit' => 'LIMIT 5',
				'where' => 'WHERE COALESCE( ROUND( t1.position - t2.position, 0 ), 0 ) < 0',
			]
		);
	}

	/**
	 * Get tracked keywords.
	 *
	 * @param  array $args Array of arguments.
	 * @return object
	 */
	public function get_tracked_keywords( $args = [] ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'dimension' => 'query',
				'order'     => 'DESC',
				'orderBy'   => 'diffPosition',
				'limit'     => 'LIMIT 20000',
				'sub_where' => " AND query IN ( SELECT keyword from {$wpdb->prefix}rank_math_analytics_keyword_manager )",
			]
		);

		$data    = Stats::get()->get_analytics_data( $args );
		$data    = Stats::get()->set_query_as_key( $data );
		$history = $this->get_graph_data_for_keywords( \array_keys( $data ) );
		$data    = Stats::get()->set_query_position( $data, $history );

		// Add remaining keywords.
		if ( 'LIMIT 5' !== $args['limit'] ) {
			$rows = DB::keywords()->get();
			foreach ( $rows as $row ) {
				if ( ! isset( $data[ $row->keyword ] ) ) {
					$data[ $row->keyword ] = [
						'query'       => $row->keyword,
						'graph'       => [],
						'clicks'      => [
							'total'      => 0,
							'difference' => 0,
						],
						'impressions' => [
							'total'      => 0,
							'difference' => 0,
						],
						'position'    => [
							'total'      => 0,
							'difference' => 0,
						],
						'ctr'         => [
							'total'      => 0,
							'difference' => 0,
						],
						'pageviews'   => [
							'total'      => 0,
							'difference' => 0,
						],
					];
				}
			}
		}

		return $data;
	}

	/**
	 * Get winning keywords.
	 *
	 * @return object
	 */
	public function get_winning_keywords() {
		$cache_key = Stats::get()->get_cache_key( 'winning_keywords', Stats::get()->days . 'days' );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$data    = Stats::get()->get_analytics_data(
			[
				'dimension' => 'query',
				'where'     => 'WHERE COALESCE( ROUND( t1.position - t2.position, 0 ), 0 ) > 0',
			]
		);
		$data    = Stats::get()->set_query_as_key( $data );
		$history = $this->get_graph_data_for_keywords( \array_keys( $data ) );
		$data    = Stats::get()->set_query_position( $data, $history );

		set_transient( $cache_key, $data, DAY_IN_SECONDS );

		return $data;
	}

	/**
	 * Get losing keywords.
	 *
	 * @return object
	 */
	public function get_losing_keywords() {
		$cache_key = Stats::get()->get_cache_key( 'losing_keywords', Stats::get()->days . 'days' );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$data    = Stats::get()->get_analytics_data(
			[
				'order'     => 'ASC',
				'dimension' => 'query',
				'where'     => 'WHERE COALESCE( ROUND( t1.position - t2.position, 0 ), 0 ) < 0',
			]
		);
		$data    = Stats::get()->set_query_as_key( $data );
		$history = $this->get_graph_data_for_keywords( \array_keys( $data ) );
		$data    = Stats::get()->set_query_position( $data, $history );

		set_transient( $cache_key, $data, DAY_IN_SECONDS );

		return $data;
	}

	/**
	 * Get graph data.
	 *
	 * @param array $keywords Keywords to get data for.
	 *
	 * @return array
	 */
	public function get_graph_data_for_keywords( $keywords ) {
		global $wpdb;

		$interval = Stats::get()->get_sql_range( 'created' );
		$keywords = \array_map( 'esc_sql', $keywords );
		$keywords = '(\'' . join( '\', \'', $keywords ) . '\')';

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT
				query, DATE_FORMAT( created,'%%Y-%%m-%%d') as date, ROUND( AVG(position), 0 ) as position
			FROM
				{$wpdb->prefix}rank_math_analytics_gsc
			WHERE query IN {$keywords} AND created BETWEEN %s AND %s
			GROUP BY query, {$interval}
			ORDER BY created ASC",
			Stats::get()->start_date,
			Stats::get()->end_date
		);

		$data = $wpdb->get_results( $query );
		// phpcs:enable

		return array_map( [ Stats::get(), 'normalize_graph_rows' ], $data );
	}

	/**
	 * Get pages by keyword.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_keyword_pages( WP_REST_Request $request ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT DISTINCT g.page
			FROM {$wpdb->prefix}rank_math_analytics_gsc as g
			WHERE g.query = %s AND g.created BETWEEN %s AND %s
			ORDER BY g.created DESC
			LIMIT 5",
			$request->get_param( 'query' ),
			Stats::get()->start_date,
			Stats::get()->end_date
		);

		$data    = $wpdb->get_results( $query ); // phpcs:ignore
		$pages   = wp_list_pluck( $data, 'page' );
		$console = Stats::get()->get_analytics_data(
			[
				'objects'   => true,
				'pageview'  => true,
				'sub_where' => " AND page IN ('" . join( "', '", $pages ) . "')",
			]
		);

		return $console;
	}
}
