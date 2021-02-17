<?php
/**
 * The Analytics Module
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use stdClass;
use WP_Error;
use WP_REST_Request;
use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Posts class.
 */
class Posts {

	use Hooker;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Posts
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Posts ) ) {
			$instance = new Posts();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function setup() {
		$this->filter( 'rank_math/analytics/single/report', 'add_badges', 10, 1 );
		$this->filter( 'rank_math/analytics/single/report', 'add_backlinks', 10, 1 );
		$this->filter( 'rank_math/analytics/single/report', 'add_ranking_keywords', 10, 1 );
		$this->filter( 'rank_math/analytics/single/report', 'get_graph_data_for_post', 10, 1 );
		$this->filter( 'rank_math/analytics/get_posts_rows_by_objects', 'get_posts_rows_by_objects', 10, 2 );
	}

	/**
	 * Get posts by objects.
	 *
	 * @param  boolean         $result Check.
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_posts_rows_by_objects( $result, WP_REST_Request $request ) {
		$per_page = 25;
		$offset   = ( $request->get_param( 'page' ) - 1 ) * $per_page;

		$objects   = Stats::get()->get_objects_by_score( $request );
		$objects   = Links::get_links_by_objects( $objects );
		$pages     = isset( $objects['rows'] ) ? \array_keys( $objects['rows'] ) : [];
		$pageviews = Pageviews::get_pageviews( [ 'pages' => $pages ] );
		$pageviews = Stats::get()->set_page_as_key( $pageviews['rows'] );
		$console   = Stats::get()->get_analytics_data(
			[
				'orderBy'   => 'diffImpressions',
				'pageview'  => true,
				'limit'     => "LIMIT 0, {$per_page}",
				'sub_where' => " AND page IN ('" . join( "', '", $pages ) . "')",
			]
		);

		$new_rows = [];
		foreach ( $objects['rows'] as $object ) {
			$page = $object['page'];

			if ( isset( $pageviews[ $page ] ) ) {
				$object['pageviews'] = [
					'total'      => (int) $pageviews[ $page ]['pageviews'],
					'difference' => (int) $pageviews[ $page ]['difference'],
				];
			}

			if ( isset( $console[ $page ] ) ) {
				$object = \array_merge( $console[ $page ], $object );
			}

			if ( ! isset( $object['links'] ) ) {
				$object['links'] = new stdClass();
			}

			$new_rows[ $page ] = $object;
		}

		$history  = $this->get_graph_data_for_pages( $pages );
		$new_rows = Stats::get()->set_page_position_graph( $new_rows, $history );

		return [
			'rows'      => $new_rows,
			'rowsFound' => $objects['rowsFound'],
		];
	}

	/**
	 * Get ranking keywords.
	 *
	 * @param  object $post  Post object.
	 * @return object
	 */
	public function add_ranking_keywords( $post ) {
		$page    = $post->page;
		$data    = Stats::get()->get_analytics_data(
			[
				'dimension' => 'query',
				'limit'     => 'LIMIT 20',
				'orderBy'   => 't1.impressions',
				'sub_where' => "AND page = '{$page}'",
			]
		);
		$data    = Stats::get()->set_query_as_key( $data );
		$history = Keywords::get()->get_graph_data_for_keywords( \array_keys( $data ) );

		$post->rankingKeywords = Stats::get()->set_query_position( $data, $history ); // phpcs:ignore

		return $post;
	}

	/**
	 * Add backlinks.
	 *
	 * @param  object $post  Post object.
	 * @return object
	 */
	public function add_backlinks( $post ) {
		$post->backlinks = [
			'total'      => 0,
			'previous'   => 0,
			'difference' => 0,
		];

		return $post;
	}

	/**
	 * Add badges.
	 *
	 * @param  object $post  Post object.
	 * @return object
	 */
	public function add_badges( $post ) {
		$post->badges = [
			'clicks'      => $this->get_position_for_badges( 'clicks', $post->page ),
			'traffic'     => $this->get_position_for_badges( 'traffic', $post->page ),
			'keywords'    => $this->get_position_for_badges( 'query', $post->page ),
			'impressions' => $this->get_position_for_badges( 'impressions', $post->page ),
		];

		return $post;
	}

	/**
	 * Get positio for badges.
	 *
	 * @param  string $column Column name.
	 * @param  string $page   Page url.
	 * @return integer
	 */
	public function get_position_for_badges( $column, $page ) {
		$start = strtotime( '-30 days ', Stats::get()->end );
		if ( 'traffic' === $column ) {
			$rows = DB::traffic()
				->select( 'page' )
				->selectSum( 'pageviews', 'pageviews' )
				->whereBetween( 'created', [ $start, Stats::get()->end_date ] )
				->groupBy( 'page' )
				->orderBy( 'pageviews', 'DESC' )
				->limit( 5 );
		} else {
			$rows = DB::analytics()
				->select( 'page' )
				->whereBetween( 'created', [ $start, Stats::get()->end_date ] )
				->groupBy( 'page' )
				->orderBy( $column, 'DESC' )
				->limit( 5 );
		}

		if ( 'impressions' === $column || 'click' === $column ) {
			$rows->selectSum( $column, $column );
		}

		if ( 'query' === $column ) {
			$rows->selectCount( 'DISTINCT(query)', 'keywords' );
		}

		$rows = $rows->get( ARRAY_A );
		foreach ( $rows as $index => $row ) {
			if ( $page === $row['page'] ) {
				return $index + 1;
			}
		}

		return 99;
	}

	/**
	 * Get graph data.
	 *
	 * @param  object $post  Post object.
	 * @return object
	 */
	public function get_graph_data_for_post( $post ) {
		global $wpdb;

		$data     = new stdClass();
		$page     = $post->page;
		$interval = Stats::get()->get_sql_range( 'created' );

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT
				DATE_FORMAT( created,'%%Y-%%m-%%d') as date, SUM( clicks ) as clicks, SUM(impressions) as impressions, ROUND( AVG(position), 0 ) as position, ROUND( AVG(ctr), 2 ) as ctr
			FROM
				{$wpdb->prefix}rank_math_analytics_gsc
			WHERE AND created BETWEEN %s AND %s AND page LIKE '%{$page}'
			GROUP BY {$interval}
			ORDER BY created ASC",
			Stats::get()->start_date,
			Stats::get()->end_date
		);
		$analytics = $wpdb->get_results( $query );
		// phpcs:enable

		$traffic = DB::traffic()
			->select( 'DATE_FORMAT( created,\'%Y-%m-%d\') as date' )
			->selectSum( 'pageviews', 'pageviews' )
			->where( 'page', $page )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->groupBy( $interval )
			->orderBy( 'created', 'ASC' )
			->get();

		$keywords = DB::analytics()
			->distinct()
			->select( 'DATE_FORMAT( created,\'%Y-%m-%d\') as date' )
			->selectCount( 'query', 'keywords' )
			->whereLike( 'page', $page )
			->whereBetween( 'created', [ Stats::get()->start_date, Stats::get()->end_date ] )
			->groupBy( $interval )
			->orderBy( 'created', 'ASC' )
			->get();

		// Convert types.
		$analytics = array_map( [ Stats::get(), 'normalize_graph_rows' ], $analytics );
		$traffic   = array_map( [ Stats::get(), 'normalize_graph_rows' ], $traffic );
		$keywords  = array_map( [ Stats::get(), 'normalize_graph_rows' ], $keywords );

		$intervals = Stats::get()->get_intervals();

		// Merge for performance.
		$data = Stats::get()->get_date_array(
			$intervals['dates'],
			[
				'clicks'      => [],
				'impressions' => [],
				'position'    => [],
				'ctr'         => [],
				'keywords'    => [],
				'pageviews'   => [],
			]
		);
		$data = Stats::get()->get_merge_data_graph( $analytics, $data, $intervals['map'] );
		$data = Stats::get()->get_merge_data_graph( $traffic, $data, $intervals['map'] );
		$data = Stats::get()->get_merge_data_graph( $keywords, $data, $intervals['map'] );
		$data = Stats::get()->get_graph_data_flat( $data );

		$post->graph = array_values( $data );

		return $post;
	}

	/**
	 * Get posts summary.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_posts_rows_by_pageviews( WP_REST_Request $request ) {
		// Pagination.
		$per_page  = 25;
		$offset    = ( $request->get_param( 'page' ) - 1 ) * $per_page;
		$data      = Pageviews::get_pageviews_with_object( [ 'limit' => "LIMIT {$offset}, {$per_page}" ] );
		$pageviews = Stats::get()->set_page_as_key( $data['rows'] );
		$pages     = \array_keys( $pageviews );
		$console   = Stats::get()->get_analytics_data(
			[
				'limit'     => 'LIMIT 100',
				'objects'   => false,
				'sub_where' => " AND page IN ('" . join( "', '", $pages ) . "')",
			]
		);

		foreach ( $pageviews as $page => &$pageview ) {
			$pageview['pageviews'] = [
				'total'      => (int) $pageview['pageviews'],
				'difference' => (int) $pageview['difference'],
			];

			if ( isset( $console[ $page ] ) ) {
				unset( $console[ $page ]['pageviews'] );
				$pageview = \array_merge( $pageview, $console[ $page ] );
			}
		}

		$history   = $this->get_graph_data_for_pages( $pages );
		$pageviews = Stats::get()->set_page_position_graph( $pageviews, $history );

		$data['rows'] = $pageviews;
		return $data;
	}

	/**
	 * Get winning posts.
	 *
	 * @return object
	 */
	public function get_winning_posts() {
		global $wpdb;

		$cache_key = Stats::get()->get_cache_key( 'winning_posts', Stats::get()->days . 'days' );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$rows = Stats::get()->get_analytics_data(
			[
				'objects'  => true,
				'pageview' => true,
				'where'    => 'WHERE COALESCE( ROUND( t1.position - t2.position, 0 ), 0 ) > 0',
			]
		);

		$history = $this->get_graph_data_for_pages( \array_keys( $rows ) );
		$rows    = Stats::get()->set_page_position_graph( $rows, $history );

		set_transient( $cache_key, $rows, DAY_IN_SECONDS );

		return $rows;
	}

	/**
	 * Get losing posts.
	 *
	 * @return object
	 */
	public function get_losing_posts() {
		global $wpdb;

		$cache_key = Stats::get()->get_cache_key( 'losing_posts', Stats::get()->days . 'days' );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$rows = Stats::get()->get_analytics_data(
			[
				'order'    => 'ASC',
				'objects'  => true,
				'pageview' => true,
				'where'    => 'WHERE COALESCE( ROUND( t1.position - t2.position, 0 ), 0 ) < 0',
			]
		);

		$history = $this->get_graph_data_for_pages( \array_keys( $rows ) );
		$rows    = Stats::get()->set_page_position_graph( $rows, $history );

		set_transient( $cache_key, $rows, DAY_IN_SECONDS );

		return $rows;
	}

	/**
	 * Get graph data.
	 *
	 * @param array $pages Pages to get data for.
	 *
	 * @return array
	 */
	public function get_graph_data_for_pages( $pages ) {
		global $wpdb;

		$interval = Stats::get()->get_sql_range( 'created' );
		$pages    = \array_map( 'esc_sql', $pages );
		$pages    = '(\'' . join( '\', \'', $pages ) . '\')';

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT
				page, DATE_FORMAT( created,'%%Y-%%m-%%d') as date, ROUND( AVG(position), 0 ) as position
			FROM
				{$wpdb->prefix}rank_math_analytics_gsc
			WHERE page IN {$pages} AND created BETWEEN %s AND %s
			GROUP BY page, {$interval}
			ORDER BY created ASC",
			Stats::get()->start_date,
			Stats::get()->end_date
		);

		$data = $wpdb->get_results( $query );
		// phpcs:enable

		return array_map( [ Stats::get(), 'normalize_graph_rows' ], $data );
	}
}
