<?php
/**
 * The REST API.
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Schema;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Controller;
use RankMath\Helper;
use RankMath\Schema\DB;
use RankMath\Traits\Meta;
use RankMath\Rest\Sanitize;

defined( 'ABSPATH' ) || exit;

/**
 * Rest class.
 */
class Rest extends WP_REST_Controller {

	use Meta;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = \RankMath\Rest\Rest_Helper::BASE;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/saveTemplate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_template' ],
				'args'                => [
					'schema' => [
						'required'          => true,
						'description'       => esc_html__( 'Schema to add.', 'rank-math-pro' ),
						'validate_callback' => [ '\\RankMath\\Rest\\Rest_Helper', 'is_param_empty' ],
					],
				],
				'permission_callback' => [ $this, 'get_permissions_check' ],
			]
		);
	}

	/**
	 * Update metadata.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function save_template( WP_REST_Request $request ) {
		$sanitizer = Sanitize::get();
		$schema    = $request->get_param( 'schema' );
		$post_id   = $request->get_param( 'postId' );

		foreach ( $schema as $id => $value ) {
			$schema[ $id ] = $sanitizer->sanitize( $id, $value );
		}

		if ( $post_id ) {
			DB::delete_schema_data( $post_id );
		}

		$meta_key    = 'rank_math_schema_' . $schema['@type'];
		$template_id = wp_insert_post(
			[
				'ID'          => $post_id,
				'post_status' => 'publish',
				'post_type'   => 'rank_math_schema',
				'post_title'  => $schema['metadata']['title'],
				'meta_input'  => [
					$meta_key => $schema,
				],
			]
		);

		return [
			'id'   => $template_id,
			'link' => get_edit_post_link( $template_id ),
		];
	}

	/**
	 * Checks whether a given request has permission to read post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public static function get_permissions_check( $request ) { // phpcs:ignore
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_cannot_edit',
			__( 'Sorry, you are not allowed to save template.', 'rank-math-pro' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
}
