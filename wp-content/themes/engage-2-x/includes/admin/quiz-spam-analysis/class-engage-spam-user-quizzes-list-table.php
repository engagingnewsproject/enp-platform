<?php
/**
 * Admin list table: ENP quizzes owned by users with the spam_user role.
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
 * Lists quizzes for spam_user owners with bulk export, draft, and permanent delete.
 */
class Engage_Spam_User_Quizzes_List_Table extends WP_List_Table {

	/**
	 * Rows after filters, before pagination.
	 *
	 * @var object[]
	 */
	protected $all_filtered = array();

	/**
	 * post_id => risk meta.
	 *
	 * @var array<int, array{tier: string, score: string}>
	 */
	protected $risk_by_post = array();

	/**
	 * quiz_id => post_id for current page items.
	 *
	 * @var array<int, int>
	 */
	protected $post_map = array();

	/**
	 * @param array<string, mixed> $args Optional constructor args.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array_merge(
				array(
					'singular' => 'quiz',
					'plural'   => 'quizzes',
					'ajax'     => false,
				),
				$args
			)
		);
	}

	/**
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
	 * Bypass stale get_column_headers() cache (same pattern as Embed Sites table).
	 *
	 * @return array{0: array<string, string>, 1: string[], 2: array<string, mixed>, 3: string}
	 */
	protected function get_column_info() {
		if ( isset( $this->_column_headers ) && is_array( $this->_column_headers ) && 4 === count( $this->_column_headers ) ) {
			return $this->_column_headers;
		}
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$primary  = 'quiz_title';
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
		return $this->_column_headers;
	}

	/**
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'quiz_title';
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return array(
			'export_csv'        => __( 'Export CSV', 'engage' ),
			'set_to_draft'      => __( 'Set to draft', 'engage' ),
			'permanent_delete'  => __( 'Permanently delete', 'engage' ),
		);
	}

	/**
	 * @return array<string, string|array{0: string, 1: bool}>
	 */
	protected function get_sortable_columns(): array {
		return array(
			'quiz_id'         => array( 'quiz_id', false ),
			'quiz_title'      => array( 'quiz_title', false ),
			'quiz_status'     => array( 'quiz_status', false ),
			'quiz_owner'      => array( 'quiz_owner', false ),
			'quiz_created_at' => array( 'quiz_created_at', false ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'cb'              => '<input type="checkbox" />',
			'quiz_id'         => __( 'Quiz ID', 'engage' ),
			'quiz_title'      => __( 'Title', 'engage' ),
			'quiz_status'     => __( 'Status', 'engage' ),
			'quiz_owner'      => __( 'Owner', 'engage' ),
			'wp_post'         => __( 'WP post', 'engage' ),
			'quiz_created_at' => __( 'Created', 'engage' ),
			'risk_tier'       => __( 'Risk tier', 'engage' ),
			'risk_score'      => __( 'Risk score', 'engage' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_views(): array {
		$current = isset( $_GET['quiz_status_view'] ) ? sanitize_key( wp_unslash( $_GET['quiz_status_view'] ) ) : 'all';
		if ( ! in_array( $current, array( 'all', 'published', 'draft', 'enp_deleted' ), true ) ) {
			$current = 'all';
		}

		$counts = engage_quiz_count_spam_user_quizzes_by_view();
		$base   = engage_quiz_spam_user_quizzes_admin_url();

		$views = array();
		foreach ( array(
			'all'         => __( 'All', 'engage' ),
			'published'   => __( 'Published', 'engage' ),
			'draft'       => __( 'Draft', 'engage' ),
			'enp_deleted' => __( 'Marked deleted in ENP', 'engage' ),
		) as $key => $label ) {
			$url = add_query_arg( 'quiz_status_view', $key, $base );
			if ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) {
				$url = add_query_arg( 's', sanitize_text_field( wp_unslash( $_GET['s'] ) ), $url );
			}
			$count = isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0;
			$text  = sprintf(
				/* translators: 1: view label, 2: count */
				__( '%1$s (%2$s)', 'engage' ),
				$label,
				number_format_i18n( $count )
			);
			$class = $current === $key ? ' class="current"' : '';
			$views[ $key ] = '<a href="' . esc_url( $url ) . '"' . $class . '>' . esc_html( $text ) . '</a>';
		}

		return $views;
	}

	/**
	 * @param string $text     Button label.
	 * @param string $input_id Input id base.
	 */
	public function search_box( $text, $input_id ): void {
		$input_id = $input_id . '-search-input';
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( wp_unslash( $_REQUEST['order'] ) ) . '" />';
		}
		if ( isset( $_GET['quiz_status_view'] ) ) {
			echo '<input type="hidden" name="quiz_status_view" value="' . esc_attr( sanitize_key( wp_unslash( $_GET['quiz_status_view'] ) ) ) . '" />';
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
	 * Loads rows, handles export, sorts and paginates.
	 */
	public function prepare_items(): void {
		$status_view = isset( $_GET['quiz_status_view'] ) ? sanitize_key( wp_unslash( $_GET['quiz_status_view'] ) ) : 'all';
		if ( ! in_array( $status_view, array( 'all', 'published', 'draft', 'enp_deleted' ), true ) ) {
			$status_view = 'all';
		}

		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$items = engage_quiz_fetch_spam_user_quizzes( $status_view, $search );

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'quiz_id';
		$order   = isset( $_GET['order'] ) && 'desc' === strtolower( wp_unslash( $_GET['order'] ) ) ? 'desc' : 'asc';

		if ( in_array( $orderby, array( 'quiz_id', 'quiz_title', 'quiz_status', 'quiz_owner', 'quiz_created_at' ), true ) ) {
			usort(
				$items,
				static function ( $a, $b ) use ( $orderby, $order ) {
					$av = isset( $a->$orderby ) ? $a->$orderby : '';
					$bv = isset( $b->$orderby ) ? $b->$orderby : '';
					if ( 'quiz_id' === $orderby || 'quiz_owner' === $orderby ) {
						$cmp = (int) $av <=> (int) $bv;
					} else {
						$cmp = strcasecmp( (string) $av, (string) $bv );
					}
					return 'desc' === $order ? -$cmp : $cmp;
				}
			);
		}

		$this->all_filtered = $items;

		$action = $this->current_action();
		if ( 'export_csv' === $action ) {
			check_admin_referer( 'bulk-quizzes' );
			$selected = isset( $_REQUEST['quiz'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['quiz'] ) ) : array();
			$selected = array_values( array_filter( $selected ) );
			engage_quiz_stream_spam_user_quiz_csv( $items, $selected );
		}

		$per_page     = $this->get_items_per_page( 'spam_user_quizzes_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $items );
		$total_pages  = $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 1;
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

		$page_items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );

		$quiz_ids = array();
		foreach ( $page_items as $row ) {
			$quiz_ids[] = (int) $row->quiz_id;
		}
		$this->post_map = engage_quiz_map_post_ids_for_quizzes( $quiz_ids );

		$post_ids = array_values( array_filter( $this->post_map ) );
		$this->risk_by_post = engage_quiz_map_risk_meta_for_posts( $post_ids );

		$this->items = $page_items;
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="quiz[]" value="%s" />',
			esc_attr( (string) (int) $item->quiz_id )
		);
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_quiz_id( object $item ): string {
		return esc_html( (string) (int) $item->quiz_id );
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_quiz_title( object $item ): string {
		return esc_html( (string) $item->quiz_title );
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_quiz_status( object $item ): string {
		if ( isset( $item->quiz_is_deleted ) && (int) $item->quiz_is_deleted !== 0 ) {
			return '<span style="color:#b32d2e">' . esc_html__( 'Deleted in ENP', 'engage' ) . '</span>';
		}
		return esc_html( (string) $item->quiz_status );
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_quiz_owner( object $item ): string {
		$oid = isset( $item->quiz_owner ) ? (int) $item->quiz_owner : 0;
		$login = isset( $item->owner_login ) ? (string) $item->owner_login : '';
		if ( $oid <= 0 ) {
			return '—';
		}
		$url = admin_url( 'user-edit.php?user_id=' . $oid );
		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html( $login ? $login : (string) $oid )
		);
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_wp_post( object $item ): string {
		$qid     = (int) $item->quiz_id;
		$post_id = isset( $this->post_map[ $qid ] ) ? (int) $this->post_map[ $qid ] : 0;
		if ( $post_id > 0 ) {
			return sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $post_id, 'raw' ) ),
				esc_html( (string) $post_id )
			);
		}
		return '<span class="engage-not-synced" style="color:#b32d2e">' . esc_html__( 'Not synced', 'engage' ) . '</span>';
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_quiz_created_at( object $item ): string {
		return isset( $item->quiz_created_at ) ? esc_html( (string) $item->quiz_created_at ) : '—';
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_risk_tier( object $item ): string {
		$qid     = (int) $item->quiz_id;
		$post_id = isset( $this->post_map[ $qid ] ) ? (int) $this->post_map[ $qid ] : 0;
		if ( $post_id > 0 && isset( $this->risk_by_post[ $post_id ]['tier'] ) && '' !== $this->risk_by_post[ $post_id ]['tier'] ) {
			return esc_html( $this->risk_by_post[ $post_id ]['tier'] );
		}
		return '—';
	}

	/**
	 * @param object $item Row.
	 */
	protected function column_risk_score( object $item ): string {
		$qid     = (int) $item->quiz_id;
		$post_id = isset( $this->post_map[ $qid ] ) ? (int) $this->post_map[ $qid ] : 0;
		if ( $post_id > 0 && isset( $this->risk_by_post[ $post_id ]['score'] ) && '' !== $this->risk_by_post[ $post_id ]['score'] ) {
			return esc_html( $this->risk_by_post[ $post_id ]['score'] );
		}
		return '—';
	}

	/**
	 * @param object $item Row.
	 * @param string $column_name Column key.
	 */
	protected function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Returns all filtered rows (for bulk handlers that need full list context).
	 *
	 * @return object[]
	 */
	public function get_all_filtered_items(): array {
		return $this->all_filtered;
	}

	public function no_items(): void {
		if ( empty( engage_quiz_get_spam_owner_ids() ) ) {
			esc_html_e( 'No users with the spam_user role (excluding administrators).', 'engage' );
			return;
		}
		esc_html_e( 'No quizzes found for spam users matching this filter.', 'engage' );
	}
}
