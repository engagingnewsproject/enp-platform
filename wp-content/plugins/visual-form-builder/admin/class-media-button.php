<?php
/**
 * Class that handles the Media Button display
 *
 */
class Visual_Form_Builder_Media_Button {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'media_buttons', array( $this, 'add_button' ), 999 );
		add_action( 'wp_ajax_vfb-media-button', array( $this, 'display' ) );
	}

	/**
	 * Add button above visual editor
	 *
	 * @access public
	 * @return void
	 */
	public function add_button() {
		// Check permission before display
		if ( !current_user_can( 'manage_options' ) )
			return;

		$button_url = add_query_arg(
			array(
				'page'   => 'visual-form-builder',
				'action' => 'vfb-media-button',
				'width'  => 600,
				'height' => 550,
			),
			wp_nonce_url( admin_url( 'admin-ajax.php' ), 'vfb_media_button' )
		);
	?>
		<a href="<?php echo esc_url( $button_url ); ?>" class="button add_media thickbox" title="<?php _e( 'Add Visual Form Builder form', 'visual-form-builder' ); ?>">
			<span class="dashicons dashicons-feedback" style="color:#888; display: inline-block; width: 18px; height: 18px; vertical-align: text-top; margin: 0 4px 0 0;"></span>
			<?php _e( 'Add Form', 'visual-form-builder' ); ?>
		</a>
	<?php
	}

	/**
	 * Displays the form after add_button is clicked
	 *
	 * @access public
	 * @return void
	 */
	public function display() {
		global $wpdb;

		check_admin_referer( 'vfb_media_button' );

		// Sanitize the sql orderby
		$order = sanitize_sql_orderby( 'form_id ASC' );

		// Build our forms as an object
		$forms = $wpdb->get_results( "SELECT form_id, form_title FROM " . VFB_WP_FORMS_TABLE_NAME . " ORDER BY $order" );

	?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$( '#add_vfb_form' ).submit(function(e){
					e.preventDefault();

					window.send_to_editor( '[vfb id=' + $( '#vfb_forms' ).val() + ']' );

					window.tb_remove();
				});
			});
	    </script>
		<div>
			<form id="add_vfb_form" class="media-upload-form type-form validate">
				<h3><?php _e( 'Insert Visual Form Builder form', 'visual-form-builder' ); ?></h3>
				<p><?php _e( 'Select a form below to insert into any Post or Page.', 'visual-form-builder' ); ?></p>
				<select id="vfb_forms" name="vfb_forms">
					<?php foreach( $forms as $form ) : ?>
						<option value="<?php echo $form->form_id; ?>"><?php echo $form->form_title; ?></option>
					<?php endforeach; ?>
				</select>
				<?php
					submit_button(
						__( 'Add Form', 'visual-form-builder' ),
						'primary',
						'' // leave blank so "name" attribute will not be added
					);
				?>
			</form>
		</div>
	<?php
		die(1);
	}
}
