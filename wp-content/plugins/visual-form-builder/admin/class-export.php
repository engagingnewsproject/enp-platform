<?php
/**
 * Class that controls the Export page view
 *
 */
class Visual_Form_Builder_Export {
	/**
	 * delimiter
	 *
	 * @var mixed
	 * @access public
	 */
	public $delimiter;

	/**
	 * default_cols
	 *
	 * @var mixed
	 * @access public
	 */
	public $default_cols;

	/**
	 * [__construct description]
	 */
	public function __construct(){
		global $wpdb;

		// CSV delimiter
		$this->delimiter = apply_filters( 'vfb_csv_delimiter', ',' );

		// Setup our default columns
		$this->default_cols = array(
			'entries_id' 		=> __( 'Entries ID' , 'visual-form-builder'),
			'date_submitted' 	=> __( 'Date Submitted' , 'visual-form-builder'),
			'ip_address' 		=> __( 'IP Address' , 'visual-form-builder'),
			'subject' 			=> __( 'Subject' , 'visual-form-builder'),
			'sender_name' 		=> __( 'Sender Name' , 'visual-form-builder'),
			'sender_email' 		=> __( 'Sender Email' , 'visual-form-builder'),
			'emails_to' 		=> __( 'Emailed To' , 'visual-form-builder'),
		);

		// AJAX for loading new entry checkboxes
		add_action( 'wp_ajax_visual_form_builder_export_load_options', array( $this, 'ajax_load_options' ) );

		// AJAX for getting entries count
		add_action( 'wp_ajax_visual_form_builder_export_entries_count', array( $this, 'ajax_entries_count' ) );

		$this->process_export_action();
	}

	/**
	 * Display the export form
	 *
	 * @since 1.7
	 *
	 */
	public function display(){
		global $wpdb;

		// Query to get all forms
		$order = sanitize_sql_orderby( 'form_id ASC' );
		$where = apply_filters( 'vfb_pre_get_forms_export', '' );
		$forms = $wpdb->get_results( "SELECT form_id, form_key, form_title FROM " . VFB_WP_FORMS_TABLE_NAME . " WHERE 1=1 $where ORDER BY $order" );

		if ( !$forms ) {
			echo sprintf(
				'<div class="vfb-form-alpha-list"><h3 id="vfb-no-forms">You currently do not have any forms.  Click on the <a href="%1$s">New Form</a> button to get started.</h3></div>',
				esc_url( admin_url( 'admin.php?page=vfb-add-new' ) )
			);

			return;
		}

		$entries_count = $this->count_entries( $forms[0]->form_id );

		// Return nothing if no entries found
		if ( !$entries_count ) {
			$no_entries = __( 'No entries to pull field names from.', 'visual-form-builder' );
		}
		else {
			$limit = $entries_count > 1000 ? 1000 : $entries_count;

			// Safe to get entries now
			$entries = $wpdb->get_results( $wpdb->prepare( "SELECT data FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE form_id = %d AND entry_approved = 1 LIMIT %d", $forms[0]->form_id, $limit ), ARRAY_A );

			// Get columns
			$columns = $this->get_cols( $entries );

			// Get JSON data
			$data = json_decode( $columns, true );
		}
		?>
        <form method="post" id="vfb-export">
        	<p><?php _e( 'Backup and save some or all of your Visual Form Builder data.', 'visual-form-builder' ); ?></p>
        	<p><?php _e( 'Once you have saved the file, you will be able to import Visual Form Builder Pro data from this site into another site.', 'visual-form-builder' ); ?></p>
        	<h3><?php _e( 'Choose what to export', 'visual-form-builder' ); ?></h3>

        	<p><label><input type="radio" name="vfb-content" value="forms" disabled="disabled" /> <?php _e( 'Forms', 'visual-form-builder' ); ?></label></p>
        	<p class="description"><?php _e( 'This will contain all of your forms, fields, and email design settings', 'visual-form-builder' ); ?>.<br><strong>*<?php _e( 'Only available in Visual Form Builder Pro', 'visual-form-builder' ); ?>*</strong></p>

        	<p><label><input type="radio" name="vfb-content" value="entries" checked="checked" /> <?php _e( 'Entries', 'visual-form-builder' ); ?></label></p>

        	<ul id="entries-filters" class="vfb-export-filters">
        		<li><p class="description"><?php _e( 'This will export entries in either a .csv, .txt, or .xls and cannot be used with the Import.  If you need to import entries on another site, please use the All data option above.', 'visual-form-builder' ); ?></p></li>
        		<!-- Format -->
        		<li>
        			<label class="vfb-export-label" for="format"><?php _e( 'Format', 'visual-form-builder' ); ?>:</label>
        			<select name="format">
        				<option value="csv" selected="selected"><?php _e( 'Comma Separated (.csv)', 'visual-form-builder' ); ?></option>
        				<option value="txt" disabled="disabled"><?php _e( 'Tab Delimited (.txt) - Pro only', 'visual-form-builder' ); ?></option>
        				<option value="xls" disabled="disabled"><?php _e( 'Excel (.xls) - Pro only', 'visual-form-builder' ); ?></option>
        			</select>
        		</li>
        		<!-- Forms -->
        		<li>
		        	<label class="vfb-export-label" for="form_id"><?php _e( 'Form', 'visual-form-builder' ); ?>:</label>
		            <select id="vfb-export-entries-forms" name="entries_form_id">
<?php
						foreach ( $forms as $form ) {
							echo sprintf(
								'<option value="%1$d" id="%2$s">%1$d - %3$s</option>',
								$form->form_id,
								$form->form_key,
								stripslashes( $form->form_title )
							);
						}
?>
					</select>
        		</li>
        		<!-- Date Range -->
        		<li>
        			<label class="vfb-export-label"><?php _e( 'Date Range', 'visual-form-builder' ); ?>:</label>
        			<select name="entries_start_date">
        				<option value="0">Start Date</option>
        				<?php $this->months_dropdown(); ?>
        			</select>
        			<select name="entries_end_date">
        				<option value="0">End Date</option>
        				<?php $this->months_dropdown(); ?>
        			</select>
        		</li>
        		<!-- Pages to Export -->
				<?php $num_pages = ceil( $entries_count / 1000 ); ?>
				<li id="vfb-export-entries-pages" style="display:<?php echo ( $entries_count > 1000 ) ? 'list-item' : 'none'; ?>">
					<label class="vfb-export-label"><?php _e( 'Page to Export', 'visual-form-builder' ); ?>:</label>
					<select id="vfb-export-entries-rows" name="entries_page">
<?php
					for ( $i = 1; $i <= $num_pages; $i++ ) {
						echo sprintf( '<option value="%1$d">%1$s</option>', $i );
					}
?>
					</select>
					<p class="description"><?php _e( 'A large number of entries have been detected for this form. Only 1000 entries can be exported at a time.', 'visual-form-builder' ); ?></p>
				</li>
				<!-- Fields -->
        		<li>
        			<label class="vfb-export-label"><?php _e( 'Fields', 'visual-form-builder' ); ?>:</label>

        			<p>
        				<a id="vfb-export-select-all" href="#"><?php _e( 'Select All', 'visual-form-builder' ); ?></a>
        				<a id="vfb-export-unselect-all" href="#"><?php _e( 'Unselect All', 'visual-form-builder' ); ?></a>
        			</p>

        			<div id="vfb-export-entries-fields">
	        		<?php
						if ( isset( $no_entries ) )
							echo $no_entries;
						else
							echo $this->build_options( $data );
					 ?>
        			</div>
        		</li>
        	</ul>

         <?php submit_button( __( 'Download Export File', 'visual-form-builder' ) ); ?>
        </form>
<?php
	}


	/**
	 * Build the entries export array
	 *
	 * @since 1.7
	 *
	 * @param array $args Filters defining what should be included in the export
	 */
	public function export_entries( $args = array() ) {
		global $wpdb;

		// Set inital fields as a string
		$initial_fields = implode( ',', $this->default_cols );

		$defaults = array(
			'content' 		=> 'entries',
			'format' 		=> 'csv',
			'form_id' 		=> 0,
			'start_date' 	=> false,
			'end_date' 		=> false,
			'page'			=> 0,
			'fields'		=> $initial_fields,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = '';

		$limit = '0,1000';

		if ( 'entries' == $args['content'] ) {
			if ( 0 !== $args['form_id'] )
				$where .= $wpdb->prepare( " AND form_id = %d", $args['form_id'] );

			if ( $args['start_date'] )
				$where .= $wpdb->prepare( " AND date_submitted >= %s", date( 'Y-m-d', strtotime( $args['start_date'] ) ) );

			if ( $args['end_date'] )
				$where .= $wpdb->prepare( " AND date_submitted < %s", date( 'Y-m-d', strtotime( '+1 month', strtotime( $args['end_date'] ) ) ) );

			if ( $args['page'] > 1 )
				$limit = ( $args['page'] - 1 ) * 1000 . ',1000';
		}

		$form_id = ( 0 !== $args['form_id'] ) ? $args['form_id'] : null;

		$entries = $wpdb->get_results( "SELECT * FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE entry_approved = 1 $where ORDER BY entries_id ASC LIMIT $limit" );
		$form_key = $wpdb->get_var( $wpdb->prepare( "SELECT form_key, form_title FROM " . VFB_WP_FORMS_TABLE_NAME . " WHERE form_id = %d", $args['form_id'] ) );
		$form_title = $wpdb->get_var( null, 1 );

		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty($sitename) ) $sitename .= '.';
		$filename = $sitename . 'vfb.' . "$form_key." . date( 'Y-m-d' ) . ".{$args['format']}";

		$content_type = 'text/csv';

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( "Content-Type: $content_type; charset=" . get_option( 'blog_charset' ), true );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		// Get columns
		$columns = $this->get_cols( $entries );

		// Get JSON data
		$data = json_decode( $columns, true );

		// Build array of fields to display
		$fields = !is_array( $args['fields'] ) ? array_map( 'trim', explode( ',', $args['fields'] ) ) : $args['fields'];

		// Strip slashes from header values
		$fields = array_map( 'stripslashes', $fields );

		// Build CSV
		$this->csv( $data, $fields );
	}

	/**
	 * Build the entries as JSON
	 *
	 * @since 1.7
	 *
	 * @param array $entries The resulting database query for entries
	 */
	public function get_cols( $entries ) {

		// Initialize row index at 0
		$row = 0;
		$output = array();

		// Loop through all entries
		foreach ( $entries as $entry ) {
			foreach ( $entry as $key => $value ) {
				switch ( $key ) {
					case 'entries_id':
					case 'date_submitted':
					case 'ip_address':
					case 'subject':
					case 'sender_name':
					case 'sender_email':
						$output[ $row ][ stripslashes( $this->default_cols[ $key ] ) ] = $value;
					break;

					case 'emails_to':
						$output[ $row ][ stripslashes( $this->default_cols[ $key ] ) ] = implode( ',', maybe_unserialize( $value ) );
					break;

					case 'data':
						// Unserialize value only if it was serialized
						$fields = maybe_unserialize( $value );

						// Make sure there are no errors with unserializing before proceeding
						if ( is_array( $fields ) ) {
							// Loop through our submitted data
							foreach ( $fields as $field_key => $field_value ) {
								// Cast each array as an object
								$obj = (object) $field_value;

								// Decode the values so HTML tags can be stripped
								$val = wp_specialchars_decode( $obj->value, ENT_QUOTES );

								switch ( $obj->type ) {
									case 'fieldset' :
									case 'section' :
									case 'instructions' :
									case 'page-break' :
									case 'verification' :
									case 'secret' :
									case 'submit' :
										break;

									case 'address' :

										$val = str_replace( array( '<p>', '</p>', '<br>' ), array( '', "\n", "\n" ), $val );

										$output[ $row ][ stripslashes( $obj->name ) . "{{{$obj->id}}}" ] =  $val;

										break;

									case 'html' :

										$output[ $row ][ stripslashes( $obj->name ) . "{{{$obj->id}}}" ] =  $val;

										break;

									default :

										$val = wp_strip_all_tags( $val );
										$output[ $row ][ stripslashes( $obj->name ) . "{{{$obj->id}}}" ] =  $val;

										break;
								} //end $obj switch
							} // end $fields loop
						}
					break;
				} //end $key switch
			} // end $entry loop
			$row++;
		} //end $entries loop

		return json_encode( $output );
	}

	/**
	 * [count_entries description]
	 * @param  [type] $form_id [description]
	 * @return [type]          [description]
	 */
	public function count_entries( $form_id ) {
		global $wpdb;

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE form_id = %d", $form_id ) );

		if ( !$count )
			return 0;

		return $count;
	}

	/**
	 * [get_form_IDs description]
	 * @param  [type] $form_id [description]
	 * @return [type]          [description]
	 */
	public function get_form_IDs( $form_id = null ) {
		global $wpdb;

		$where = '';

		if ( $form_id )
			$where .= $wpdb->prepare( " AND form_id = %d", $form_id );

		$form_ids = $wpdb->get_col( "SELECT DISTINCT form_id FROM " . VFB_WP_FORMS_TABLE_NAME . " WHERE 1=1 $where" );

		if ( !$form_ids )
			return;

		return $form_ids;
	}

	/**
	 * [get_field_IDs description]
	 * @param  [type] $form_id [description]
	 * @return [type]          [description]
	 */
	public function get_field_IDs( $form_id = null ) {
		global $wpdb;

		$where = '';

		if ( $form_id )
			$where .= $wpdb->prepare( " AND form_id = %d", $form_id );

		$field_ids = $wpdb->get_col( "SELECT DISTINCT field_id FROM " . VFB_WP_FIELDS_TABLE_NAME . " WHERE 1=1 $where" );

		if ( !$field_ids )
			return;

		return $field_ids;
	}

	/**
	 * [get_entry_IDs description]
	 * @param  [type] $form_id [description]
	 * @return [type]          [description]
	 */
	public function get_entry_IDs( $form_id = null ) {
		global $wpdb;

		$where = '';

		if ( $form_id ) {
			$where .= $wpdb->prepare( " AND form_id = %d", $form_id );

			$count = $this->count_entries( $form_id );
			$where .= " LIMIT $count";
		}

		$entry_ids = $wpdb->get_col( "SELECT DISTINCT entries_id FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE entry_approved = 1 $where" );

		if ( !$entry_ids )
			return;

		return $entry_ids;
	}

	/**
	 * Return the entries data formatted for CSV
	 *
	 * @since 1.7
	 *
	 * @param array $data The multidimensional array of entries data
	 * @param array $fields The selected fields to export
	 */
	public function csv( $data, $fields ) {
		// Open file with PHP wrapper
		$fh = @fopen( 'php://output', 'w' );

		$rows = $fields_clean = $fields_header = array();

		// Decode special characters
		foreach ( $fields as $field ) {
			// Strip unique ID for a clean header
			$search = preg_replace( '/{{(\d+)}}/', '', $field );
			$fields_header[] = wp_specialchars_decode( $search, ENT_QUOTES );

			// Field with unique ID to use as matching data
			$fields_clean[] = wp_specialchars_decode( $field, ENT_QUOTES );
		}

		// Build headers
		fputcsv( $fh, $fields_header, $this->delimiter );

		// Build table rows and cells
		foreach ( $data as $row ) {

			foreach ( $fields_clean as $label ) {
				$label = wp_specialchars_decode( $label );
				$rows[ $label ] =  ( isset( $row[ $label ] ) && in_array( $label, $fields_clean ) ) ? $row[ $label ] : '';
			}

			fputcsv( $fh, $rows, $this->delimiter );
		}

		// Close the file
		fclose( $fh );

		exit();
	}

	/**
	 * Build the checkboxes when changing forms
	 *
	 * @since 2.6.8
	 *
	 * @return string Either no entries or the entry headers
	 */
	public function ajax_load_options() {
		global $wpdb;

		if ( !isset( $_GET['action'] ) )
			return;

		if ( $_GET['action'] !== 'visual_form_builder_export_load_options' )
			return;

		$form_id = absint( $_GET['id'] );

		// Safe to get entries now
		$entry_ids = $this->get_entry_IDs( $form_id );

		// Return nothing if no entries found
		if ( !$entry_ids ) {
			echo __( 'No entries to pull field names from.', 'visual-form-builder' );
			wp_die();
		}

		$offset = '';
		$limit = 1000;

		if ( isset( $_GET['count'] ) ) {
			$limit = ( $_GET['count'] < 1000 ) ? absint( $_GET['count'] ) : 1000;
		}
		elseif ( isset( $_GET['offset'] ) ) {
			// Reset offset/page to a zero index
			$offset = absint( $_GET['offset'] ) - 1;

			// Calculate the offset
			$offset_num = $offset * 1000;

			// If page is 2 or greater, set the offset (page 2 is equal to offset 1 because of zero index)
			$offset = $offset >= 1 ? "OFFSET $offset_num" : '';
		}

		$entries = $wpdb->get_results( "SELECT data FROM " . VFB_WP_ENTRIES_TABLE_NAME . " WHERE form_id = $form_id AND entry_approved = 1 LIMIT $limit $offset", ARRAY_A );

		// Get columns
		$columns = $this->get_cols( $entries );

		// Get JSON data
		$data = json_decode( $columns, true );

		echo $this->build_options( $data );

		wp_die();
	}

	/**
	 * [ajax_entries_count description]
	 * @return [type] [description]
	 */
	public function ajax_entries_count() {
		global $wpdb;

		if ( !isset( $_GET['action'] ) )
			return;

		if ( $_GET['action'] !== 'visual_form_builder_export_entries_count' )
			return;

		$form_id = absint( $_GET['id'] );

		echo $this->count_entries( $form_id );

		wp_die();
	}

	/**
	 * [build_options description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function build_options( $data ) {
		$output = '';

		$array = array();
		foreach ( $data as $row ) {
			$array = array_merge( $row, $array );
		}

		$array = array_keys( $array );
		$array = array_values( array_merge( $this->default_cols, $array ) );
		$array = array_map( 'stripslashes', $array );

		foreach ( $array as $k => $v ) {
			$selected = ( in_array( $v, $this->default_cols ) ) ? ' checked="checked"' : '';

			// Strip unique ID for a clean list
			$search = preg_replace( '/{{(\d+)}}/', '', $v );

			$output .= sprintf( '<label for="vfb-display-entries-val-%1$d"><input name="entries_columns[]" class="vfb-display-entries-vals" id="vfb-display-entries-val-%1$d" type="checkbox" value="%4$s" %3$s> %2$s</label><br>', $k, $search, $selected, esc_attr( $v ) );
		}

		return $output;
	}

	/**
	 * Return the selected export type
	 *
	 * @since 1.7
	 *
	 * @return string|bool The type of export
	 */
	public function export_action() {
		if ( isset( $_POST['vfb-content'] ) )
			return $_POST['vfb-content'];

		return false;
	}

	/**
	 * Determine which export process to run
	 *
	 * @since 1.7
	 *
	 */
	public function process_export_action() {
		$args = array();

		if ( !isset( $_POST['vfb-content'] ) || 'entries' == $_POST['vfb-content'] ) {
			$args['content'] = 'entries';

			$args['format'] = 'csv';

			if ( isset( $_POST['entries_form_id'] ) )
				$args['form_id'] = (int) $_POST['entries_form_id'];

			if ( isset( $_POST['entries_start_date'] ) || isset( $_POST['entries_end_date'] ) ) {
				$args['start_date'] = $_POST['entries_start_date'];
				$args['end_date'] = $_POST['entries_end_date'];
			}

			if ( isset( $_POST['entries_columns'] ) )
				$args['fields'] = array_map( 'esc_html',  $_POST['entries_columns'] );

			if ( isset( $_POST['entries_page'] ) )
				$args['page'] = absint( $_POST['entries_page'] );
		}

		switch( $this->export_action() ) {
			case 'entries' :
				$this->export_entries( $args );
				die(1);
			break;
		}
	}

	/**
	 * Display Year/Month filter
	 *
	 * @since 1.7
	 */
	public function months_dropdown() {
		global $wpdb, $wp_locale;

		$where = apply_filters( 'vfb_pre_get_entries', '' );

	    $months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( forms.date_submitted ) AS year, MONTH( forms.date_submitted ) AS month
			FROM " . VFB_WP_ENTRIES_TABLE_NAME . " AS forms
			WHERE 1=1 $where
			ORDER BY forms.date_submitted DESC
		" );

		$month_count = count( $months );

		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;

		$m = isset( $_POST['m'] ) ? (int) $_POST['m'] : 0;

		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year )
				continue;

			$month = zeroise( $arc_row->month, 2 );
			$year = $arc_row->year;

			printf( "<option value='%s'>%s</option>\n",
				esc_attr( $arc_row->year . '-' . $month ),
				sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
			);
		}
	}
}
