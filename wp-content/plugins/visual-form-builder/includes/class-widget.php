<?php

add_action( 'widgets_init', 'vfb_register_widgets' );

function vfb_register_widgets() {
	register_widget( 'VisualFormBuilder_Widget' );
}

/**
 * Class that builds our Import page
 *
 * @since 2.7
 */
class VisualFormBuilder_Widget extends WP_Widget {
	/**
	 * field_table_name
	 *
	 * @var mixed
	 * @access public
	 */
	public $field_table_name;

	/**
	 * form_table_name
	 *
	 * @var mixed
	 * @access public
	 */
	public $form_table_name;

	/**
	 * entries_table_name
	 *
	 * @var mixed
	 * @access public
	 */
	public $entries_table_name;

	public function __construct(){
		global $wpdb;

		/* Setup global database table names */
		$this->field_table_name 	= $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name 		= $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name 	= $wpdb->prefix . 'visual_form_builder_entries';

		parent::__construct(
			'vfb_widget',
			__( 'Visual Form Builder', 'vfb-pro' ),
			array(
				'classname'   => 'vfb_widget_class',
				'description' => __( 'Visual Form Builder Widget', 'visual-form-builder' ),
			)
		);
	}

	public function form( $instance ) {
		global $wpdb;

		// Query to get all forms
		$order = sanitize_sql_orderby( 'form_id ASC' );
		$where = apply_filters( 'vfb_pre_get_forms_widget', '' );
		$forms = $wpdb->get_results( "SELECT * FROM $this->form_table_name WHERE 1=1 $where ORDER BY $order" );

		$instance = wp_parse_args( (array) $instance );

		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'id' ); ?>"><?php _e( 'Form to display:', 'visual-form-builder' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'id' ); ?>" name="<?php echo $this->get_field_name( 'id' ); ?>" class="widefat">
			<?php
				foreach ( $forms as $form ) {
					echo sprintf(
						'<option value="%1$d" id="%2$s"%3$s>%1$d - %4$s</option>',
						absint( $form->form_id ),
						esc_html( $form->form_key ),
						selected( $form->form_id, $instance['id'], 1 ),
						wp_specialchars_decode( esc_html( stripslashes( $form->form_title ) ), ENT_QUOTES )
					);
				}
			?>
			</select>
		</p>
		<?php
	}

	public function widget( $args, $instance ) {
		extract( $args );

		$form_id = absint( $instance['id'] );

		echo $before_widget;

		// Title
		if ( !empty( $instance['title'] ) )
			echo $args['before_title'] . $instance['title'] . $args['after_title'];

		// Print the output
		echo do_shortcode( "[vfb id=$form_id]" );

		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['id'] = !empty( $new_instance['id'] ) ? absint( $new_instance['id'] ) : '';
		$instance['title'] = !empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}
