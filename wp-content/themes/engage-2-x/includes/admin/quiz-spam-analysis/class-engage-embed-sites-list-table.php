<?php
/**
 * Admin table that lists each external website URL that embeds quizzes, and how many quizzes use it.
 *
 * Extends core WP_List_Table; hides this WordPress site’s own host using engage_quiz_is_local_embed_url().
 * Implements pagination, search, sortable headers, a quiz-count filter, bulk CSV export, and row actions.
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
 * Risk columns aggregate synced quiz CPT meta: max score and worst tier (high &gt; medium &gt; low) among quizzes embedded on that URL.
 */
class Engage_Embed_Sites_List_Table extends WP_List_Table {

	/**
	 * Row count after search / min-quiz filters, before pagination (for subsubsub views).
	 *
	 * @var int
	 */
	protected $total_filtered = 0;

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

		add_filter( 'hidden_columns', array( $this, 'filter_never_hide_embed_site_columns' ), 10, 3 );
	}

	/**
	 * Reads bulk action from top or bottom dropdown (parent only checks `action`).
	 *
	 * @return string|false
	 */
	public function current_action() {
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) ) {
			return false;
		}
		if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) {
			return sanitize_key( wp_unslash( $_REQUEST['action'] ) );
		}
		if ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) {
			return sanitize_key( wp_unslash( $_REQUEST['action2'] ) );
		}
		return false;
	}

	/**
	 * Layman: WordPress remembers the first column-header list it builds for a screen; if that ran before our theme loaded, it can be an empty list forever. We build headers from this table’s own definitions instead of that stale cache.
	 *
	 * Technical: Mirrors WP_List_Table::get_column_info() but uses `$this->get_columns()` instead of get_column_headers(), which static-caches per request.
	 *
	 * @return array{0: array<string, string>, 1: string[], 2: array<string, mixed>, 3: string}
	 */
	protected function get_column_info() {
		if ( isset( $this->_column_headers ) && is_array( $this->_column_headers ) ) {
			if ( 4 === count( $this->_column_headers ) ) {
				return $this->_column_headers;
			}
			$column_headers = array( array(), array(), array(), $this->get_primary_column_name() );
			foreach ( $this->_column_headers as $key => $value ) {
				$column_headers[ $key ] = $value;
			}
			$this->_column_headers = $column_headers;
			return $this->_column_headers;
		}

		$columns            = $this->get_columns();
		$hidden             = get_hidden_columns( $this->screen );
		$sortable_columns   = $this->get_sortable_columns();
		$_sortable_filtered = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );
		$sortable           = array();
		foreach ( $_sortable_filtered as $id => $data ) {
			if ( empty( $data ) ) {
				continue;
			}
			$data = (array) $data;
			if ( ! isset( $data[1] ) ) {
				$data[1] = false;
			}
			if ( ! isset( $data[2] ) ) {
				$data[2] = '';
			}
			if ( ! isset( $data[3] ) ) {
				$data[3] = false;
			}
			if ( ! isset( $data[4] ) ) {
				$data[4] = false;
			}
			$sortable[ $id ] = $data;
		}
		$primary               = $this->get_primary_column_name();
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
		return $this->_column_headers;
	}

	/**
	 * Layman: Same idea as get_column_info — the default primary-column logic reads from the stale get_column_headers() cache; we base it on this table’s get_columns() instead.
	 */
	protected function get_primary_column_name() {
		$columns = $this->get_columns();
		$default = $this->get_default_primary_column_name();
		if ( ! isset( $columns[ $default ] ) ) {
			$default = $this->get_default_primary_column_name();
		}
		$column = apply_filters( 'list_table_primary_column', $default, $this->screen->id );
		if ( empty( $column ) || ! isset( $columns[ $column ] ) ) {
			$column = $default;
		}
		return $column;
	}

	/**
	 * Layman: If every column were marked “hidden” in Screen Options, the table body would get a zero-width row and look empty; we keep the real columns visible.
	 *
	 * @param string[]   $hidden         Column keys hidden via Screen Options.
	 * @param \WP_Screen $screen         Current admin screen.
	 * @param bool       $_use_defaults Whether defaults apply; required by `hidden_columns` signature.
	 * @return string[]
	 */
	public function filter_never_hide_embed_site_columns( $hidden, $screen, $_use_defaults ) {
		if ( ! $screen || $screen->id !== $this->screen->id ) {
			return $hidden;
		}
		$keys = array( 'cb', 'embed_site_url', 'risk_tier', 'risk_score', 'quiz_count' );
		return array_values( array_diff( (array) $hidden, $keys ) );
	}

	/**
	 * Layman: Per embed URL, shows how “bad” the riskiest linked quiz is (after you’ve run Analyse Quizzes).
	 *
	 * Technical: Single SQL grouping enp_embed_quiz by embed_site_id, joining posts where post_meta _enp_quiz_id matches ENP quiz_id via numeric equality (avoids utf8mb4_unicode_ci vs utf8mb4_unicode_520_ci errors on meta_value); MAX(score), MAX(tier rank).
	 *
	 * @param object[] $items Row objects; augmented with embed_max_risk_score, embed_tier_rank, embed_worst_risk_tier.
	 */
	private function enrich_items_with_embed_risk_totals( array &$items ): void {
		foreach ( $items as &$item ) {
			$item->embed_worst_risk_tier = '';
			$item->embed_max_risk_score  = null;
			$item->embed_tier_rank       = 0;
		}
		unset( $item );

		if ( ! defined( 'ENGAGE_QUIZ_META_RISK_SCORE' ) || ! defined( 'ENGAGE_QUIZ_META_RISK_TIER' ) ) {
			return;
		}

		$ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $r ) {
							return isset( $r->embed_site_id ) ? (int) $r->embed_site_id : 0;
						},
						$items
					)
				)
			)
		);
		if ( empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$t_eq         = $wpdb->prefix . 'enp_embed_quiz';
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "
			SELECT eq.embed_site_id,
				MAX(CAST(NULLIF(TRIM(COALESCE(score.meta_value, '')), '') AS UNSIGNED)) AS max_score,
				MAX(CASE LOWER(TRIM(COALESCE(tier.meta_value, '')))
					WHEN 'high' THEN 3
					WHEN 'medium' THEN 2
					WHEN 'low' THEN 1
					ELSE 0 END) AS tier_rank
			FROM `{$t_eq}` eq
			INNER JOIN {$wpdb->postmeta} qm ON qm.meta_key = %s AND CAST( qm.meta_value AS UNSIGNED ) = eq.quiz_id
			INNER JOIN {$wpdb->posts} p ON p.ID = qm.post_id AND p.post_type = 'quiz' AND p.post_status NOT IN ('trash', 'auto-draft')
			LEFT JOIN {$wpdb->postmeta} score ON score.post_id = p.ID AND score.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} tier ON tier.post_id = p.ID AND tier.meta_key = %s
			WHERE eq.embed_site_id IN ($placeholders)
			GROUP BY eq.embed_site_id
		";

		$prepare_args = array_merge(
			array( '_enp_quiz_id', ENGAGE_QUIZ_META_RISK_SCORE, ENGAGE_QUIZ_META_RISK_TIER ),
			$ids
		);
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$prepare_args ), ARRAY_A );

		$map = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$sid = isset( $row['embed_site_id'] ) ? (int) $row['embed_site_id'] : 0;
				if ( $sid <= 0 ) {
					continue;
				}
				$raw_max = $row['max_score'] ?? null;
				$map[ $sid ] = array(
					'max_score' => ( null !== $raw_max && '' !== $raw_max && is_numeric( $raw_max ) ) ? (int) $raw_max : null,
					'tier_rank' => isset( $row['tier_rank'] ) ? (int) $row['tier_rank'] : 0,
				);
			}
		}

		foreach ( $items as &$item ) {
			$sid = (int) $item->embed_site_id;
			if ( ! isset( $map[ $sid ] ) ) {
				continue;
			}
			$item->embed_max_risk_score  = $map[ $sid ]['max_score'];
			$item->embed_tier_rank       = $map[ $sid ]['tier_rank'];
			$item->embed_worst_risk_tier = $this->embed_tier_rank_to_slug( $map[ $sid ]['tier_rank'] );
		}
		unset( $item );
	}

	/**
	 * Layman: Turns the numeric worst-tier rank from SQL back into the same labels stored on quiz posts (low / medium / high).
	 */
	private function embed_tier_rank_to_slug( int $rank ): string {
		if ( 3 === $rank ) {
			return 'high';
		}
		if ( 2 === $rank ) {
			return 'medium';
		}
		if ( 1 === $rank ) {
			return 'low';
		}
		return '';
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_views(): array {
		$base = admin_url( 'edit.php?post_type=quiz&page=engage-quiz-embed-sites' );
		$all  = sprintf(
			/* translators: %s: number of embed sites matching current filters */
			__( 'All <span class="count">(%s)</span>', 'engage' ),
			number_format_i18n( max( 0, (int) $this->total_filtered ) )
		);
		return array(
			'all' => '<a href="' . esc_url( $base ) . '" class="current">' . wp_kses_post( $all ) . '</a>',
		);
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return array(
			'export_csv' => __( 'Export URLs (CSV)', 'engage' ),
		);
	}

	/**
	 * @return array<string, string|array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns(): array {
		return array(
			'embed_site_url' => array( 'embed_site_url', false ),
			'risk_tier'      => array( 'risk_tier', false ),
			'risk_score'     => array( 'risk_score', false ),
			'quiz_count'     => array( 'quiz_count', false ),
		);
	}

	/**
	 * Column definitions for the table header row.
	 *
	 * @return array<string, string> Column key => translated title.
	 */
	public function get_columns(): array {
		return array(
			'cb'             => '<input type="checkbox" />',
			'embed_site_url' => __( 'Site URL', 'engage' ),
			'risk_tier'      => __( 'Risk tier', 'engage' ),
			'risk_score'     => __( 'Risk score', 'engage' ),
			'quiz_count'     => __( 'Quizzes', 'engage' ),
		);
	}

	/**
	 * Preserves sort fields across search submit; always shows the search box (core hides it when the current page is empty).
	 *
	 * @param string $text     Submit button label.
	 * @param string $input_id Base id for the search input.
	 */
	public function search_box( $text, $input_id ) {
		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( wp_unslash( $_REQUEST['order'] ) ) . '" />';
		}
		if ( isset( $_GET['min_quizzes'] ) && is_numeric( $_GET['min_quizzes'] ) ) {
			echo '<input type="hidden" name="min_quizzes" value="' . esc_attr( (string) (int) $_GET['min_quizzes'] ) . '" />';
		}

		?>
<p class="search-box">
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
	<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
		<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
		<?php
	}

	/**
	 * Minimum quiz count filter (left of pagination) plus search box on the right.
	 *
	 * @param string $which top|bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$min_val = isset( $_GET['min_quizzes'] ) && is_numeric( $_GET['min_quizzes'] ) ? (int) $_GET['min_quizzes'] : '';
		?>
	<div class="alignleft actions">
		<label for="engage-embed-min-quizzes" class="screen-reader-text"><?php esc_html_e( 'Minimum quizzes', 'engage' ); ?></label>
		<input type="number" name="min_quizzes" id="engage-embed-min-quizzes" class="small-text" min="0" step="1"
			placeholder="<?php echo esc_attr__( 'Min quizzes', 'engage' ); ?>"
			value="<?php echo '' !== $min_val ? esc_attr( (string) (int) $min_val ) : ''; ?>" />
		<?php
		submit_button( __( 'Filter', 'engage' ), '', 'filter_action', false, array( 'id' => 'embed-site-query-submit' ) );
		?>
	</div>
		<?php
		$this->search_box( __( 'Search URLs', 'engage' ), 'engage_embed_site' );
	}

	/**
	 * Runs SQL, filters local hosts, then search / min quizzes / sort / bulk export / pagination.
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
			$this->items        = array();
			$this->total_filtered = 0;
			return;
		}

		$items = array_values(
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

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		if ( '' !== $search ) {
			$items = array_values(
				array_filter(
					$items,
					static function ( $row ) use ( $search ) {
						$url = (string) $row->embed_site_url;
						if ( stripos( $url, $search ) !== false ) {
							return true;
						}
						if ( is_numeric( $search ) ) {
							return (int) $row->quiz_count === (int) $search;
						}
						return false;
					}
				)
			);
		}

		if ( isset( $_GET['min_quizzes'] ) && is_numeric( $_GET['min_quizzes'] ) ) {
			$min_q = max( 0, (int) $_GET['min_quizzes'] );
			if ( $min_q > 0 ) {
				$items = array_values(
					array_filter(
						$items,
						static function ( $row ) use ( $min_q ) {
							return (int) $row->quiz_count >= $min_q;
						}
					)
				);
			}
		}

		$this->enrich_items_with_embed_risk_totals( $items );

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'embed_site_url';
		if ( ! in_array( $orderby, array( 'embed_site_url', 'quiz_count', 'risk_tier', 'risk_score' ), true ) ) {
			$orderby = 'embed_site_url';
		}
		$order = ( isset( $_REQUEST['order'] ) && 'desc' === strtolower( (string) wp_unslash( $_REQUEST['order'] ) ) ) ? 'desc' : 'asc';

		$score_cmp = static function ( $x, $y ) {
			$sx = isset( $x->embed_max_risk_score ) && is_numeric( $x->embed_max_risk_score ) ? (int) $x->embed_max_risk_score : -1;
			$sy = isset( $y->embed_max_risk_score ) && is_numeric( $y->embed_max_risk_score ) ? (int) $y->embed_max_risk_score : -1;
			return $sx <=> $sy;
		};
		$tier_cmp = static function ( $x, $y ) {
			$tx = isset( $x->embed_tier_rank ) ? (int) $x->embed_tier_rank : 0;
			$ty = isset( $y->embed_tier_rank ) ? (int) $y->embed_tier_rank : 0;
			return $tx <=> $ty;
		};
		$url_cmp = static function ( $x, $y ) {
			return strcasecmp( (string) $x->embed_site_url, (string) $y->embed_site_url );
		};
		$apply_primary_order = static function ( int $cmp, string $order ) : int {
			return 'desc' === $order ? -$cmp : $cmp;
		};

		usort(
			$items,
			static function ( $a, $b ) use ( $orderby, $order, $score_cmp, $tier_cmp, $url_cmp, $apply_primary_order ) {
				$cmp = 0;
				if ( 'quiz_count' === $orderby ) {
					$cmp = (int) $a->quiz_count <=> (int) $b->quiz_count;
				} elseif ( 'risk_score' === $orderby ) {
					$cmp = $score_cmp( $a, $b );
				} elseif ( 'risk_tier' === $orderby ) {
					$cmp = $tier_cmp( $a, $b );
				} else {
					$cmp = $url_cmp( $a, $b );
				}
				if ( 0 !== $cmp ) {
					return $apply_primary_order( $cmp, $order );
				}
				if ( 'embed_site_url' === $orderby ) {
					return 0;
				}
				if ( 'risk_score' === $orderby ) {
					$cmp = $tier_cmp( $a, $b );
					if ( 0 !== $cmp ) {
						return $apply_primary_order( $cmp, $order );
					}
					return $url_cmp( $a, $b );
				}
				if ( 'risk_tier' === $orderby ) {
					$cmp = $score_cmp( $a, $b );
					if ( 0 !== $cmp ) {
						return $apply_primary_order( $cmp, $order );
					}
					return $url_cmp( $a, $b );
				}
				$cmp = $score_cmp( $a, $b );
				if ( 0 !== $cmp ) {
					return $apply_primary_order( $cmp, $order );
				}
				return $url_cmp( $a, $b );
			}
		);

		$action = $this->current_action();
		if ( 'export_csv' === $action ) {
			check_admin_referer( 'bulk-embed_sites' );
			$selected = isset( $_REQUEST['embed_site'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['embed_site'] ) ) : array();
			$selected = array_values( array_filter( $selected ) );
			$this->send_csv_export( $items, $selected );
		}

		$this->total_filtered = count( $items );
		$per_page             = $this->get_items_per_page( 'embed_sites_per_page', 20 );
		$current_page         = $this->get_pagenum();
		$total_items          = $this->total_filtered;
		$total_pages          = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 1;
		if ( $total_pages < 1 ) {
			$total_pages = 1;
		}

		if ( $total_items > 0 ) {
			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => $total_pages,
				)
			);
		}

		$this->items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );
	}

	/**
	 * Stream a UTF-8 CSV of selected embed sites (id, url, quiz_count, risk_tier, risk_score).
	 *
	 * @param object[] $all_filtered Full list after filters (not only current page).
	 * @param int[]    $ids           Selected embed_site_id values.
	 */
	private function send_csv_export( array $all_filtered, array $ids ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export.', 'engage' ) );
		}

		if ( empty( $ids ) ) {
			wp_safe_redirect(
				add_query_arg(
					'engage_embed_export',
					'none',
					admin_url( 'edit.php?post_type=quiz&page=engage-quiz-embed-sites' )
				)
			);
			exit;
		}

		$map = array();
		foreach ( $all_filtered as $row ) {
			$map[ (int) $row->embed_site_id ] = $row;
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=embed-sites-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			exit;
		}
		fputcsv( $out, array( 'embed_site_id', 'embed_site_url', 'quiz_count', 'risk_tier', 'risk_score' ) );
		foreach ( $ids as $id ) {
			if ( ! isset( $map[ $id ] ) ) {
				continue;
			}
			$r           = $map[ $id ];
			$risk_tier   = isset( $r->embed_worst_risk_tier ) ? (string) $r->embed_worst_risk_tier : '';
			$risk_score  = ( isset( $r->embed_max_risk_score ) && is_numeric( $r->embed_max_risk_score ) ) ? (string) (int) $r->embed_max_risk_score : '';
			fputcsv(
				$out,
				array(
					(string) (int) $r->embed_site_id,
					(string) $r->embed_site_url,
					(string) (int) $r->quiz_count,
					$risk_tier,
					$risk_score,
				)
			);
		}
		fclose( $out );
		exit;
	}

	/**
	 * Row checkbox for bulk actions.
	 *
	 * @param object $item Row object.
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="embed_site[]" value="%s" />',
			esc_attr( (string) (int) $item->embed_site_id )
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
				'post_type'       => 'quiz',
				'page'            => 'engage-quiz-embed-sites',
				'embed_site_id'   => (int) $item->embed_site_id,
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
	 * Worst risk tier among synced quizzes on this embed site (high beats medium beats low).
	 *
	 * @param object $item Row enriched in prepare_items.
	 */
	protected function column_risk_tier( object $item ): string {
		$tier = isset( $item->embed_worst_risk_tier ) ? (string) $item->embed_worst_risk_tier : '';
		return $tier ? esc_html( $tier ) : '—';
	}

	/**
	 * Highest numeric risk score among synced quizzes on this embed site.
	 *
	 * @param object $item Row enriched in prepare_items.
	 */
	protected function column_risk_score( object $item ): string {
		if ( isset( $item->embed_max_risk_score ) && is_numeric( $item->embed_max_risk_score ) ) {
			return esc_html( (string) (int) $item->embed_max_risk_score );
		}
		return '—';
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
