<?php
/**
 * Admin table that lists each external website URL that embeds quizzes, and how many quizzes use it.
 *
 * Extends core WP_List_Table; hides this WordPress site’s own host using engage_quiz_is_local_embed_url().
 *
 * @package Engage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Data source: enp_embed_site joined to enp_embed_quiz; items filtered after query for “external only”.
 */
class Engage_Embed_Sites_List_Table extends WP_List_Table {

	/**
	 * Passes singular/plural screen ids into WordPress’s list table base.
	 *
	 * @param array<string, mixed> $args Optional overrides merged into parent constructor args.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array_merge(
				array(
					'singular' => 'embed_site',
					'plural'   => 'embed_sites',
					'ajax'     => false,
				),
				$args
			)
		);
	}

	/**
	 * Column definitions for the table header row.
	 *
	 * @return array<string, string> Column key => translated title.
	 */
	public function get_columns(): array {
		return array(
			'embed_site_url' => __( 'Site URL', 'engage' ),
			'quiz_count'     => __( 'Quizzes', 'engage' ),
		);
	}

	/**
	 * Runs SQL, then removes rows whose URL host is the current WordPress site (same behavior as quiz list embed column).
	 */
	public function prepare_items(): void {
		global $wpdb;

		$t_site = $wpdb->prefix . 'enp_embed_site';
		$t_eq   = $wpdb->prefix . 'enp_embed_quiz';

		$sql = "
			SELECT s.embed_site_id, s.embed_site_url, COUNT(DISTINCT eq.quiz_id) AS quiz_count
			FROM `{$t_site}` s
			INNER JOIN `{$t_eq}` eq ON s.embed_site_id = eq.embed_site_id
			GROUP BY s.embed_site_id, s.embed_site_url
			ORDER BY s.embed_site_url ASC
		";

		$rows = $wpdb->get_results( $sql );
		if ( ! is_array( $rows ) ) {
			$this->items = array();
			return;
		}

		$this->items = array_values(
			array_filter(
				$rows,
				static function ( $row ) {
					if ( ! isset( $row->embed_site_url ) ) {
						return false;
					}
					if ( ! function_exists( 'engage_quiz_is_local_embed_url' ) ) {
						return true;
					}
					return ! engage_quiz_is_local_embed_url( (string) $row->embed_site_url );
				}
			)
		);
	}

	/**
	 * Clickable site URL linking to the drill-down page for that embed_site_id.
	 *
	 * @param object $item Row: embed_site_id, embed_site_url, quiz_count.
	 */
	protected function column_embed_site_url( object $item ): string {
		$url = add_query_arg(
			array(
				'post_type'      => 'quiz',
				'page'           => 'engage-quiz-embed-sites',
				'embed_site_id' => (int) $item->embed_site_id,
			),
			admin_url( 'edit.php' )
		);
		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html( (string) $item->embed_site_url )
		);
	}

	/**
	 * Distinct quiz count for this embed site (from SQL aggregate).
	 *
	 * @param object $item Row with quiz_count.
	 */
	protected function column_quiz_count( object $item ): string {
		return esc_html( (string) (int) $item->quiz_count );
	}

	/**
	 * Fallback for unknown columns (unused here; required by WP_List_Table).
	 *
	 * @param object $item        Row object.
	 * @param string $column_name Requested column key.
	 */
	protected function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Shown when every site was filtered out or the join returns no rows.
	 */
	public function no_items(): void {
		esc_html_e( 'No external embed sites found.', 'engage' );
	}
}
