<?php
/**
 * Class that displays widgets on the WordPress dashboard
 *
 */
class Visual_Form_Builder_Dashboard_Widgets {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $id
	 * @return void
	 */
	public function __construct() {
		// Adds a Dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register our Dashboard widget
	 *
	 * @access public
	 * @return void
	 */
	public function register_widgets() {
		wp_add_dashboard_widget(
			'vfb-dashboard',
			__( 'Recent Visual Form Builder Entries', 'visual-form-builder' ),
			array( $this, 'widget_entries' ),
			array( $this, 'widget_control' )
		);

		if ( !get_option( 'vfb_dashboard_widget_options' ) ) {
			$widget_options['vfb_dashboard_recent_entries'] = array(
				'items' => 5,
			);
			update_option( 'vfb_dashboard_widget_options', $widget_options );
		}
	}

	/**
	 * Display recent submitted entries
	 * @return [type] [description]
	 */
	public function widget_entries() {
		global $wpdb;

		// Get the date/time format that is saved in the options table
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$widgets     = get_option( 'vfb_dashboard_widget_options' );
		$total_items = isset( $widgets['vfb_dashboard_recent_entries'] ) && isset( $widgets['vfb_dashboard_recent_entries']['items'] ) ? absint( $widgets['vfb_dashboard_recent_entries']['items'] ) : 5;

		$forms = $wpdb->get_var( "SELECT COUNT(*) FROM " . VFB_WP_FORMS_TABLE_NAME );

		if ( !$forms ) {
			echo sprintf(
				'<p>%1$s <a href="%2$s">%3$s</a></p>',
				__( 'You currently do not have any forms.', 'visual-form-builder' ),
				esc_url( admin_url( 'admin.php?page=vfb-add-new' ) ),
				__( 'Get started!', 'visual-form-builder' )
			);

			return;
		}

		$entries = $wpdb->get_results( $wpdb->prepare( "SELECT forms.form_title, entries.entries_id, entries.form_id, entries.sender_name, entries.sender_email, entries.date_submitted FROM " . VFB_WP_FORMS_TABLE_NAME . " AS forms INNER JOIN " . VFB_WP_ENTRIES_TABLE_NAME . " AS entries ON entries.form_id = forms.form_id ORDER BY entries.date_submitted DESC LIMIT %d", $total_items ) );

		if ( !$entries ) {
			echo sprintf( '<p>%1$s</p>', __( 'You currently do not have any entries.', 'visual-form-builder' ) );
		}
		else {

			$content = '';

			foreach ( $entries as $entry ) {
				$content .= sprintf(
					'<li><a href="%1$s">%4$s</a> via <a href="%2$s">%5$s</a> <span class="rss-date">%6$s</span><cite>%3$s</cite></li>',
					esc_url( add_query_arg( array( 'action' => 'view', 'entry' => absint( $entry->entries_id ) ), admin_url( 'admin.php?page=vfb-entries' ) ) ),
					esc_url( add_query_arg( 'form-filter', absint( $entry->form_id ), admin_url( 'admin.php?page=vfb-entries' ) ) ),
					esc_html( $entry->sender_name ),
					esc_html( $entry->sender_email ),
					esc_html( $entry->form_title ),
					date( "$date_format $time_format", strtotime( $entry->date_submitted ) )
				);
			}

			echo "<div class='rss-widget'><ul>$content</ul></div>";
		}
	}

	/**
	 * Displays the dashboard widget form control
	 * @return [type] [description]
	 */
	public function widget_control() {
		if ( !$widget_options = get_option( 'vfb_dashboard_widget_options' ) )
			$widget_options = array();

		if ( !isset( $widget_options['vfb_dashboard_recent_entries'] ) )
			$widget_options['vfb_dashboard_recent_entries'] = array();

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['vfb-widget-recent-entries'] ) ) {
			$number = absint( $_POST['vfb-widget-recent-entries']['items'] );
			$widget_options['vfb_dashboard_recent_entries']['items'] = $number;
			update_option( 'vfb_dashboard_widget_options', $widget_options );
		}

		$number = isset( $widget_options['vfb_dashboard_recent_entries']['items'] ) ? (int) $widget_options['vfb_dashboard_recent_entries']['items'] : '';

		echo sprintf(
			'<p>
			<label for="comments-number">%1$s</label>
			<input id="comments-number" name="vfb-widget-recent-entries[items]" type="text" value="%2$d" size="3" />
			</p>',
			__( 'Number of entries to show:', 'visual-form-builder' ),
			$number
		);
	}
}
