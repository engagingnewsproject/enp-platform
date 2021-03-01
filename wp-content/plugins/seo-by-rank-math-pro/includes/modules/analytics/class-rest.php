<?php
/**
 * The Global functionality of the plugin.
 *
 * Defines the functionality loaded on admin.
 *
 * @since      1.0.15
 * @package    RankMathPro
 * @subpackage RankMathPro\Rest
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use WP_Error;
use WP_REST_Server;
use RankMath\Helper;
use WP_REST_Request;
use WP_REST_Controller;
use RankMath\Admin\Admin_Helper;
use RankMathPro\Google\PageSpeed;
use RankMath\SEO_Analysis\SEO_Analyzer;

defined( 'ABSPATH' ) || exit;

/**
 * Rest class.
 */
class Rest extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = \RankMath\Rest\Rest_Helper::BASE . '/an';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/getKeywordPages',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ Keywords::get(), 'get_keyword_pages' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/postsOverview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_posts_overview' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getTrackedKeywords',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keywords' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getTrackedKeywordSummary',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keyword_summary' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/trackedKeywordsOverview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keywords_overview' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/addTrackKeyword',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'add_track_keyword' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/removeTrackKeyword',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'remove_track_keyword' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getPagespeed',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_pagespeed' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/postsRows',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ Posts::get(), 'get_posts_rows_by_pageviews' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
	}

	/**
	 * Determines if the current user can manage analytics.
	 *
	 * @return true
	 */
	public function has_permission() {
		return current_user_can( 'rank_math_analytics' );
	}

	/**
	 * Get posts overview.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_posts_overview( WP_REST_Request $request ) {
		return rest_ensure_response(
			[
				'winningPosts' => Posts::get()->get_winning_posts(),
				'losingPosts'  => Posts::get()->get_losing_posts(),
			]
		);
	}

	/**
	 * Get keywords overview.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tracked_keywords( WP_REST_Request $request ) {
		return rest_ensure_response(
			[ 'rows' => Keywords::get()->get_tracked_keywords() ]
		);
	}

	/**
	 * Get keywords summary.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tracked_keyword_summary( WP_REST_Request $request ) {
		\RankMathPro\Admin\Api::get()->get_settings();

		return rest_ensure_response( Keywords::get()->get_tracked_keywords_summary() );
	}

	/**
	 * Get keywords overview.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tracked_keywords_overview( WP_REST_Request $request ) {
		return rest_ensure_response(
			[
				'winningKeywords' => Keywords::get()->get_tracked_winning_keywords(),
				'losingKeywords'  => Keywords::get()->get_tracked_losing_keywords(),
			]
		);
	}

	/**
	 * Add track keyword to DB.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function add_track_keyword( WP_REST_Request $request ) {
		$keyword = $request->get_param( 'keyword' );
		if ( empty( $keyword ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no keyword found.', 'rank-math-pro' )
			);
		}

		if ( $this->can_add_keyword() ) {
			Keywords::get()->add_track_keyword( $keyword );
			return true;
		}

		return false;
	}

	/**
	 * Remove track keyword to DB.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function remove_track_keyword( WP_REST_Request $request ) {
		$keyword = $request->get_param( 'keyword' );
		if ( empty( $keyword ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no keyword found.', 'rank-math-pro' )
			);
		}

		$this->can_add_keyword( 'delete_keyword' );
		Keywords::get()->remove_track_keyword( $keyword );
		return true;
	}

	/**
	 * Can add keyword
	 *
	 * @param  string $func What function to execute.
	 * @return bool
	 */
	private function can_add_keyword( $func = 'can_add_keyword' ) {
		$registered = Admin_Helper::get_registration_data();
		if ( ! $registered || empty( $registered['username'] ) || empty( $registered['api_key'] ) ) {
			return false;
		}

		$response  = \RankMathPro\Admin\Api::get()->$func( $registered['username'], $registered['api_key'] );
		$available = $response['available'] - $response['taken'];
		update_option( 'rank_math_keyword_quota', $response );

		return $available >= 0;
	}

	/**
	 * Get posts overview.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_pagespeed( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );
		if ( empty( $id ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no record id found.', 'rank-math-pro' )
			);
		}

		$post_id = $request->get_param( 'objectID' );
		if ( empty( $id ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no post id found.', 'rank-math-pro' )
			);
		}

		$force = \boolval( $request->get_param( 'force' ) );

		if ( Helper::is_localhost() ) {
			return [
				'page_score'          => 0,
				'desktop_interactive' => 0,
				'desktop_pagescore'   => 0,
				'mobile_interactive'  => 0,
				'mobile_pagescore'    => 0,
				'pagespeed_refreshed' => current_time( 'mysql' ),
			];
		}

		$url = get_permalink( $post_id );
		$pre = apply_filters( 'rank_math/analytics/pre_pagespeed', false, $post_id, $force );
		if ( false !== $pre ) {
			return $pre;
		}

		if ( $force || $this->should_update_pagespeed( $id ) ) {
			// Page Score.
			$analyzer = new SEO_Analyzer();
			$score    = $analyzer->get_page_score( $url );
			$update   = [];
			if ( $score > 0 ) {
				$update['page_score'] = $score;
			}

			// PageSpeed desktop.
			$desktop = PageSpeed::get_pagespeed( $url, 'desktop' );
			if ( ! empty( $desktop ) ) {
				$update                        = \array_merge( $update, $desktop );
				$update['pagespeed_refreshed'] = current_time( 'mysql' );
			}

			// PageSpeed mobile.
			$mobile = PageSpeed::get_pagespeed( $url, 'mobile' );
			if ( ! empty( $mobile ) ) {
				$update                        = \array_merge( $update, $mobile );
				$update['pagespeed_refreshed'] = current_time( 'mysql' );
			}
		}

		if ( ! empty( $update ) ) {
			$update['id'] = $id;
			$update['object_id'] = $post_id;
			DB::update_object( $update );
		}

		return empty( $update ) ? false : $update;
	}

	/**
	 * Should update pagespeed record.
	 *
	 * @param  int $id      Database row id.
	 * @return bool
	 */
	private function should_update_pagespeed( $id ) {
		$record = DB::objects()->where( 'id', $id )->one();

		return \time() > ( \strtotime( $record->pagespeed_refreshed ) + ( DAY_IN_SECONDS * 7 ) );
	}
}
