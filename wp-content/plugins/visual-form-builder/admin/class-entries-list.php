<?php
/**
 * Class that builds our Entries table
 *
 * @since 1.2
 */
class Visual_Form_Builder_Entries_List extends Visual_Form_Builder_List_Table {
	/**
	 * [__construct description]
	 */
	function __construct(){
		global $status, $page;

		// CSV delimiter
		$this->delimiter = apply_filters( 'vfb_csv_delimiter', ',' );

		// Set parent defaults
		parent::__construct( array(
			'singular'  => 'entry',
			'plural'    => 'entries',
			'ajax'      => false
		) );

		// Handle our bulk actions
		$this->process_bulk_action();
	}

	/**
	 * Display column names. We'll handle the Form column separately.
	 *
	 * @since 1.2
	 * @returns $item string Column name
	 */
	function column_default( $item, $column_name ){
		switch ( $column_name ) {
			case 'subject':
			case 'sender_name':
			case 'sender_email':
			case 'emails_to':
			case 'date':
			case 'ip_address':
			case 'entry_id' :
				return $item[ $column_name ];
		}
	}

	/**
	 * Builds the on:hover links for the Form column
	 *
	 * @since 1.2
	 */
	function column_form( $item ){

		// Build row actions
		if ( !$this->get_entry_status() || 'all' == $this->get_entry_status() )
			$actions['view'] = sprintf( '<a href="?page=%s&action=%s&entry=%s" id="%3$s" class="view-entry">View</a>', $_GET['page'], 'view', $item['entry_id'] );

		if ( !$this->get_entry_status() || 'all' == $this->get_entry_status() )
			$actions['trash'] = sprintf( '<a href="?page=%s&action=%s&entry=%s">Trash</a>', $_GET['page'], 'trash', $item['entry_id'] );
		elseif ( $this->get_entry_status() && 'trash' == $this->get_entry_status() ) {
			$actions['restore'] = sprintf( '<a href="?page=%s&action=%s&entry=%s">%s</a>', $_GET['page'], 'restore', $item['entry_id'], __( 'Restore', 'visual-form-builder' ) );
			$actions['delete'] = sprintf( '<a href="?page=%s&action=%s&entry=%s">%s</a>', $_GET['page'], 'delete', $item['entry_id'], __( 'Delete Permanently', 'visual-form-builder' ) );
		}

		return sprintf( '%1$s %2$s', $item['form'], $this->row_actions( $actions ) );
	}

	/**
	 * Used for checkboxes and bulk editing
	 *
	 * @since 1.2
	 */
	function column_cb( $item ){
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['entry_id'] );
	}

	/**
	 * Builds the actual columns
	 *
	 * @since 1.2
	 */
	function get_columns(){
		$columns = array(
			'cb' 			=> '<input type="checkbox" />', //Render a checkbox instead of text
			'form' 			=> __( 'Form' , 'visual-form-builder'),
			'subject' 		=> __( 'Email Subject' , 'visual-form-builder'),
			'sender_name' 	=> __( 'Sender Name' , 'visual-form-builder'),
			'sender_email' 	=> __( 'Sender Email' , 'visual-form-builder'),
			'emails_to' 	=> __( 'Emailed To' , 'visual-form-builder'),
			'ip_address' 	=> __( 'IP Address' , 'visual-form-builder'),
			'date' 			=> __( 'Date Submitted' , 'visual-form-builder'),
			'entry_id'		=> __( 'Entry ID' , 'visual-form-builder'),
		);

		return $columns;
	}

	/**
	 * A custom function to get the entries and sort them
	 *
	 * @since 1.2
	 * @returns array() $cols SQL results
	 */
	function get_entries( $orderby = 'date', $order = 'ASC', $per_page, $offset = 0, $search = '' ){
		global $wpdb;

		// Set OFFSET for pagination
		$offset = ( $offset > 0 ) ? "OFFSET $offset" : '';

 		switch ( $orderby ) {
			case 'date':
				$order_col = 'date_submitted';
				break;

			case 'form':
				$order_col = 'form_title';
				break;

			case 'subject':
			case 'ip_address':
			case 'sender_name':
			case 'sender_email':
				$order_col = $orderby;
				break;

			case 'entry_id' :
				$order_col = 'entries_id';
				break;
		}

		$where = '';

		// If the form filter dropdown is used
		if ( $this->current_filter_action() )
			$where .= $wpdb->prepare( 'AND forms.form_id = %d', $this->current_filter_action() );

		// Get the month and year from the dropdown
		$m = isset( $_POST['m'] ) ? (int) $_POST['m'] : 0;

		// If a month/year has been selected, parse out the month/year and build the clause
		if ( $m > 0 ) {
			$year 	= substr( $m, 0, 4 );
			$month 	= substr( $m, -2 );

			$where .= $wpdb->prepare( " AND YEAR(date_submitted) = %d AND MONTH(date_submitted) = %d", $year, $month );
		}

		// Get the month/year from the dropdown
		$today = isset( $_GET['today'] ) ? (int) $_GET['today'] : 0;

		// Parse month/year and build the clause
		if ( $today > 0 )
			$where .= " AND entries.date_submitted >= curdate()";

		// Entries type filter
		$where .= ( $this->get_entry_status() && 'all' !== $this->get_entry_status() ) ? $wpdb->prepare( ' AND entries.entry_approved = %s', $this->get_entry_status() ) : '';

		// Always display approved entries, unless an Entries Type filter is set
		if ( !$this->get_entry_status() || 'all' == $this->get_entry_status() )
			$where .= $wpdb->prepare( ' AND entries.entry_approved = %d', 1 );

		$sql_order = sanitize_sql_orderby( "$order_col $order" );
		$cols = $wpdb->get_results( "SELECT forms.form_title, entries.entries_id, entries.form_id, entries.subject, entries.sender_name, entries.sender_email, entries.emails_to, entries.date_submitted, entries.ip_address FROM " . VFB_WP_FORMS_TABLE_NAME . " AS forms INNER JOIN " . VFB_WP_ENTRIES_TABLE_NAME . " AS entries ON entries.form_id = forms.form_id WHERE 1=1 $where $search ORDER BY $sql_order LIMIT $per_page $offset" );

		return $cols;
	}

	/**
	 * Get the entry status: All, Spam, or Trash
	 *
	 * @since 2.1
	 * @returns string Entry status
	 */
	function get_entry_status() {
		if ( !isset( $_GET['entry_status'] ) )
			return false;

		return esc_html( $_GET['entry_status'] );
	}

	/**
	 * Build the different views for the entries screen
	 *
	 * @since 2.1
	 * @returns array $status_links Status links with counts
	 */
	function get_views() {
		$status_links = array();
		$num_entries = $this->get_entries_count();
		$class = '';
		$link = '?page=vfb-entries';

		$stati = array(
			'all'    => _n_noop( 'All <span class="count">(<span class="pending-count">%s</span>)</span>', 'All <span class="count">(<span class="pending-count">%s</span>)</span>' ),
			'trash'  => _n_noop( 'Trash <span class="count">(<span class="trash-count">%s</span>)</span>', 'Trash <span class="count">(<span class="trash-count">%s</span>)</span>' )
		);

		$total_entries = (int) $num_entries->all;
		$entry_status = isset( $_GET['entry_status'] ) ? $_GET['entry_status'] : 'all';

		foreach ( $stati as $status => $label ) {
			$class = ( $status == $entry_status ) ? ' class="current"' : '';

			if ( !isset( $num_entries->$status ) )
				$num_entries->$status = 10;

			$link = add_query_arg( 'entry_status', $status, $link );

			$status_links[ $status ] = "<li class='$status'><a href='$link'$class>" . sprintf(
				translate_nooped_plural( $label, $num_entries->$status ),
				number_format_i18n( $num_entries->$status )
			) . '</a>';
		}

		return $status_links;
	}

	/**
	 * Get the number of entries for use with entry statuses
	 *
	 * @since 2.1
	 * @returns array $stats Counts of different entry types
	 */
	function get_entries_count() {
		global $wpdb;

		$stats = array();

		$entries = $wpdb->get_results( "SELECT entries.entry_approved, COUNT( * ) AS num_entries FROM " . VFB_WP_ENTRIES_TABLE_NAME . " AS entries WHERE 1=1 GROUP BY entries.entry_approved", ARRAY_A );

		$total = 0;
		$approved = array( '0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed');
		foreach ( (array) $entries as $row ) {
			// Don't count trashed toward totals
			if ( 'trash' != $row['entry_approved'] )
				$total += $row['num_entries'];
			if ( isset( $approved[ $row['entry_approved' ] ] ) )
				$stats[ $approved[ $row['entry_approved' ] ] ] = $row['num_entries'];
		}

		$stats['all'] = $total;
		foreach ( $approved as $key ) {
			if ( empty( $stats[ $key ] ) )
				$stats[ $key ] = 0;
		}

		$stats = (object) $stats;

		return $stats;
	}

	/**
	 * Setup which columns are sortable. Default is by Date.
	 *
	 * @since 1.2
	 * @returns array() $sortable_columns Sortable columns
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'form' 			=> array( 'form', false ),
			'subject' 		=> array( 'subject', false ),
			'sender_name' 	=> array( 'sender_name', false ),
			'sender_email' 	=> array( 'sender_email', false ),
			'date' 			=> array( 'date', true ),
			'entry_id'		=> array( 'entry_id', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Define our bulk actions
	 *
	 * @since 1.2
	 * @returns array() $actions Bulk actions
	 */
	function get_bulk_actions() {

		if ( !$this->get_entry_status() || 'all' == $this->get_entry_status() )
			$actions['trash'] = __( 'Move to Trash', 'visual-form-builder' );
		elseif ( $this->get_entry_status() && 'trash' == $this->get_entry_status() ) {
			$actions['restore'] = __( 'Restore', 'visual-form-builder' );
			$actions['delete'] = __( 'Delete Permanently', 'visual-form-builder' );
		}

		return $actions;
	}

	/**
	 * Process our bulk actions
	 *
	 * @since 1.2
	 */
	function process_bulk_action() {
		global $wpdb;

		$entry_id = '';

		// Set the Entry ID array
		if ( isset( $_GET['entry'] ) )
			$entry_id = (array) $_GET['entry'];

		if ( isset( $_POST['entry'] ) && is_array( $_POST['entry'] ) ) {
			$entry_id = $_POST['entry'];
		}

		switch( $this->current_action() ) :
			case 'trash' :
				foreach ( $entry_id as $id ) {
					$id = absint( $id );
					$wpdb->update( VFB_WP_ENTRIES_TABLE_NAME, array( 'entry_approved' => 'trash' ), array( 'entries_id' => $id ) );
				}
			break;

			case 'delete' :
				foreach ( $entry_id as $id ) {
					$id = absint( $id );
					$wpdb->query( $wpdb->prepare( "DELETE FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE entries_id = %d", $id ) );
				}
			break;

			case 'restore' :
				foreach ( $entry_id as $id ) {
					$id = absint( $id );
					$wpdb->update( VFB_WP_ENTRIES_TABLE_NAME, array( 'entry_approved' => 1 ), array( 'entries_id' => $id ) );
				}
			break;

			case 'delete' :
				$entry_id = ( isset( $_GET['entry'] ) && is_array( $_GET['entry'] ) ) ? $_GET['entry'] : array( $_GET['entry'] );

				global $wpdb;

				foreach ( $entry_id as $id ) {
					$id = absint( $id );
					$wpdb->query( $wpdb->prepare( "DELETE FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE entries_id = %d", $id ) );
				}
			break;
		endswitch;
	}

	/**
	 * Adds our forms filter dropdown
	 *
	 * @since 1.2
	 */
	function extra_tablenav( $which ) {
		global $wpdb;

		$cols = $wpdb->get_results( "SELECT DISTINCT forms.form_title, forms.form_id FROM " . VFB_WP_FORMS_TABLE_NAME . " AS forms ORDER BY forms.form_id ASC" );

		// Only display the dropdown on the top of the table
		if ( 'top' == $which ) {
			echo '<div class="alignleft actions">';
				$this->months_dropdown();
			echo '<select id="form-filter" name="form-filter">
				<option value="-1"' . selected( $this->current_filter_action(), -1 ) . '>' . __( 'View all forms' , 'visual-form-builder') . '</option>';

			foreach ( $cols as $form ) {
				echo sprintf( '<option value="%1$d"%2$s>%1$d - %3$s</option>',
					$form->form_id,
					selected( $this->current_filter_action(), $form->form_id ),
					$form->form_title
				);
			}

			echo '</select>
				<input type="submit" value="' . __( 'Filter' , 'visual-form-builder') . '" class="button-secondary" />
				</div>';
		}
	}

	/**
	 * Display Year/Month filter
	 *
	 * @since 2.3.1
	 */
	function months_dropdown( $post_type = '' ) {
		global $wpdb, $wp_locale;

	    $months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( forms.date_submitted ) AS year, MONTH( forms.date_submitted ) AS month
			FROM " . VFB_WP_ENTRIES_TABLE_NAME . " AS forms
			ORDER BY forms.date_submitted DESC
		" );

		$month_count = count( $months );

		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;

		$m = isset( $_POST['m'] ) ? (int) $_POST['m'] : 0;
?>
		<select name='m'>
			<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
<?php
		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year )
				continue;

			$month = zeroise( $arc_row->month, 2 );
			$year = $arc_row->year;

			printf( "<option %s value='%s'>%s</option>\n",
				selected( $m, $year . $month, false ),
				esc_attr( $arc_row->year . $month ),
				sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
			);
		}
?>
		</select>
<?php
	}

	/**
	 * Set our forms filter action
	 *
	 * @since 1.2
	 * @returns int Form ID
	 */
	function current_filter_action() {
		if ( isset( $_POST['form-filter'] ) && -1 != $_POST['form-filter'] )
			return absint( $_POST['form-filter'] );

		return false;
	}

	/**
	 * Display Search box
	 *
	 * @since 1.4
	 * @returns html Search Form
	 */
	function search_box( $text, $input_id ) {
	    parent::search_box( $text, $input_id );
	}

	/**
	 * Prepares our data for display
	 *
	 * @since 1.2
	 */
	function prepare_items() {
		global $wpdb;

		// get the current user ID
		$user = get_current_user_id();

		// get the current admin screen
		$screen = get_current_screen();

		// retrieve the "per_page" option
		$screen_option = $screen->get_option( 'per_page', 'option' );

		// retrieve the value of the option stored for the current user
		$per_page = get_user_meta( $user, $screen_option, true );

		// get the default value if none is set
		if ( empty ( $per_page ) || $per_page < 1 )
			$per_page = 20;

		// Get the date/time format that is saved in the options table
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		// What page are we looking at?
		$current_page = $this->get_pagenum();

		// Use offset for pagination
		$offset = ( $current_page - 1 ) * $per_page;

		// Get column headers
		$columns = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );

		// Get sortable columns
		$sortable = $this->get_sortable_columns();

		// Build the column headers
		$this->_column_headers = array($columns, $hidden, $sortable);

		// Get entries search terms
		$search_terms = ( !empty( $_POST['s'] ) ) ? explode( ' ', $_POST['s'] ) : array();

		$searchand = $search = '';
		// Loop through search terms and build query
		foreach( $search_terms as $term ) {
			$term = esc_sql( $wpdb->esc_like( $term ) );

			$search .= "{$searchand}((entries.subject LIKE '%{$term}%') OR (entries.sender_name LIKE '%{$term}%') OR (entries.sender_email LIKE '%{$term}%') OR (entries.emails_to LIKE '%{$term}%') OR (entries.data LIKE '%{$term}%'))";
			$searchand = ' AND ';
		}

		$search = ( !empty($search) ) ? " AND ({$search}) " : '';

		// Set our ORDER BY and ASC/DESC to sort the entries
		$orderby = ( !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'date';
		$order = ( !empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc';

		// Get the sorted entries
		$entries = $this->get_entries( $orderby, $order, $per_page, $offset, $search );

		$data = array();

		// Loop trough the entries and setup the data to be displayed for each row
		foreach ( $entries as $entry ) {
			$data[] =
				array(
					'entry_id' 		=> $entry->entries_id,
					'id' 			=> $entry->entries_id,
					'form' 			=> stripslashes( $entry->form_title ),
					'subject' 		=> stripslashes( $entry->subject ),
					'sender_name' 	=> stripslashes( $entry->sender_name ),
					'sender_email' 	=> stripslashes( $entry->sender_email ),
					'emails_to' 	=> implode( ',', unserialize( stripslashes( $entry->emails_to ) ) ),
					'date' 			=> date( "$date_format $time_format", strtotime( $entry->date_submitted ) ),
					'ip_address' 	=> $entry->ip_address,
			);
		}

		$where = '';

		// If the form filter dropdown is used
		if ( $this->current_filter_action() )
			$where .= 'AND form_id = ' . $this->current_filter_action();

		// Get the month/year from the dropdown
		$m = isset( $_POST['m'] ) ? (int) $_POST['m'] : 0;

		// Parse month/year and build the clause
		if ( $m > 0 ) {
			$year = substr( $m, 0, 4 );
			$month = substr( $m, -2 );

			$where .= " AND YEAR(date_submitted) = $year AND MONTH(date_submitted) = $month";
		}

		// Get the month/year from the dropdown
		$today = isset( $_GET['today'] ) ? (int) $_GET['today'] : 0;

		// Parse month/year and build the clause
		if ( $today > 0 )
			$where .= " AND entries.date_submitted >= curdate()";

		// Entry type filter
		$where .= ( $this->get_entry_status() && 'all' !== $this->get_entry_status() ) ? $wpdb->prepare( ' AND entries.entry_approved = %s', $this->get_entry_status() ) : '';

		// Always display approved entries, unless an Entries Type filter is set
		if ( !$this->get_entry_status() || 'all' == $this->get_entry_status() )
			$where .= $wpdb->prepare( ' AND entries.entry_approved = %d', 1 );

		// How many entries do we have?
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM " . VFB_WP_ENTRIES_TABLE_NAME . " AS entries WHERE 1=1 $where" );

		// Add sorted data to the items property
		$this->items = $data;

		// Register our pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

	/**
	 * Display the pagination.
	 * Customize default function to work with months and form drop down filters
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args, EXTR_SKIP );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

		$page_links = array();

		// Added to pick up the months dropdown
		$m = isset( $_POST['m'] ) ? (int) $_POST['m'] : 0;

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		// Modified the add_query_args to include my custom dropdowns
		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( array( 'paged' => max( 1, $current-1 ), 'm' => $m, 'form-filter' => $this->current_filter_action() ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='paged' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( array( 'paged' => min( $total_pages, $current+1 ), 'm' => $m, 'form-filter' => $this->current_filter_action() ), $current_url ) ),
			'&rsaquo;'
		);

		// Modified the add_query_args to include my custom dropdowns
		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( array( 'paged' => $total_pages, 'm' => $m, 'form-filter' => $this->current_filter_action() ), $current_url ) ),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) )
			$pagination_links_class = ' hide-if-js';
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}
}
