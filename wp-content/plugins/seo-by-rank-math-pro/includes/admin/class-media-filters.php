<?php
/**
 * Post filters for the manage posts screen.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Post filters class.
 *
 * @codeCoverageIgnore
 */
class Media_Filters {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'wp_enqueue_media', 'enqueue_media', 20 );
		$this->filter( 'ajax_query_attachments_args', 'attachments_query_filter' );
	}

	/**
	 * Enqueue assets for Media Library.
	 *
	 * @return void
	 */
	public function enqueue_media() {
		global $pagenow;
		if ( 'upload.php' !== $pagenow ) {
			return;
		}
		wp_enqueue_script( 'media-library-seo-filter', RANK_MATH_PRO_URL . 'assets/admin/js/media.js', [ 'media-editor', 'media-views', 'media-models' ], rank_math_pro()->version ); // phpcs:ignore
		wp_localize_script(
			'media-library-seo-filter',
			'RankMathProMedia',
			[
				'filters'    => [
					'missing_alt'     => __( 'Missing alt tag', 'rank-math-pro' ),
					'missing_title'   => __( 'Missing or default title tag', 'rank-math-pro' ),
					'missing_caption' => __( 'Missing caption', 'rank-math-pro' ),
				],
				'filter_all' => __( 'SEO Filters', 'rank-math-pro' ),
			]
		);
	}

	/**
	 * Modify media ajax query according to the selected SEO filter.
	 *
	 * @param  array $query Query parameters.
	 * @return array        New query parameters.
	 */
	public function attachments_query_filter( $query ) {
		if ( empty( $_POST['query']['attachment_seo_filter'] ) ) { // phpcs:ignore
			return $query;
		}

		$filter = sanitize_title( wp_unslash( $_POST['query']['attachment_seo_filter'] ) ); // phpcs:ignore
		switch ( $filter ) {
			case 'missing_alt':
				if ( ! isset( $query['meta_query'] ) ) {
					$query['meta_query'] = [];
				}
				$query['meta_query'][] = [
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				];
				break;

			case 'missing_title':
				$this->filter( 'posts_clauses', 'filter_query_attachment_titles' );
				break;

			case 'missing_caption':
				$this->filter( 'posts_clauses', 'filter_query_attachment_captions' );
				break;
		}

		return $query;
	}

	/**
	 * Filter the SQL clauses of an attachment query to match attachments where
	 * the title equals the filename.
	 *
	 * @param string[] $clauses An array including WHERE, GROUP BY, JOIN, ORDER BY,
	 *                          DISTINCT, fields (SELECT), and LIMITS clauses.
	 * @return string[] The modified array of clauses.
	 */
	public function filter_query_attachment_titles( $clauses ) {
		remove_filter( 'posts_clauses', __FUNCTION__ );
		$clauses['where'] .= " AND ( post_title = '' OR ( ( post_title LIKE '%.png' OR post_title LIKE '%.jpg' OR post_title LIKE '%.gif' OR post_title LIKE '%.jpeg' ) AND INSTR( guid, post_title ) != 0 ) )";

		return $clauses;
	}

	/**
	 * Filter the SQL clauses of an attachment query to match attachments where
	 * caption is empty.
	 *
	 * @param string[] $clauses An array including WHERE, GROUP BY, JOIN, ORDER BY,
	 *                          DISTINCT, fields (SELECT), and LIMITS clauses.
	 * @return string[] The modified array of clauses.
	 */
	public function filter_query_attachment_captions( $clauses ) {
		remove_filter( 'posts_clauses', __FUNCTION__ );
		$clauses['where'] .= " AND post_excerpt = ''";

		return $clauses;
	}
}
