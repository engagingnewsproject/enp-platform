<?php
/**
 * [Visual_Form_Builder_Meta_Boxes description]
 */
class Visual_Form_Builder_Meta_Boxes {
	/**
	 * [add_meta_boxes description]
	 */
	public function add_meta_boxes() {
		add_meta_box( 'vfb_form_items_meta_box', __( 'Form Items', 'visual-form-builder' ), array( $this, 'form_items' ), 'visual-form-builder', 'side', 'high' );
		add_meta_box( 'vfb_form_media_button_tip', __( 'Display Forms', 'visual-form-builder' ), array( $this, 'display_forms' ), 'visual-form-builder', 'side', 'low' );
	}

	/**
	 * [form_items description]
	 * @return [type] [description]
	 */
	public function form_items() {
	?>
		<div class="taxonomydiv">
			<p><strong><?php _e( 'Click' , 'visual-form-builder'); ?></strong> <?php _e( 'to Add a Field' , 'visual-form-builder'); ?> <img id="add-to-form" alt="" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" class="waiting spinner" /></p>
			<ul class="posttype-tabs add-menu-item-tabs" id="vfb-field-tabs">
				<li class="tabs"><a href="#standard-fields" class="nav-tab-link vfb-field-types"><?php _e( 'Standard' , 'visual-form-builder'); ?></a></li>
			</ul>
			<div id="standard-fields" class="tabs-panel tabs-panel-active">
				<ul class="vfb-fields-col-1">
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-fieldset">Fieldset</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-text"><b></b>Text</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-checkbox"><b></b>Checkbox</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-select"><b></b>Select</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-datepicker"><b></b>Date</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-url"><b></b>URL</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-digits"><b></b>Number</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-phone"><b></b>Phone</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-file"><b></b>File Upload</a></li>
				</ul>
				<ul class="vfb-fields-col-2">
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-section">Section</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-textarea"><b></b>Textarea</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-radio"><b></b>Radio</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-address"><b></b>Address</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-email"><b></b>Email</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-currency"><b></b>Currency</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-time"><b></b>Time</a></li>

					<li><a href="#" class="vfb-draggable-form-items" id="form-element-html"><b></b>HTML</a></li>

					<li><a href="#" class="vfb-draggable-form-items" id="form-element-instructions"><b></b>Instructions</a></li>
				</ul>
				<div class="clear"></div>
			</div> <!-- #standard-fields -->
		</div> <!-- .taxonomydiv -->
		<div class="clear"></div>
	<?php
	}

	/**
	 * [display_forms description]
	 * @return [type] [description]
	 */
	public function display_forms() {
	?>
		<p><?php _e( 'Add forms to your Posts or Pages by locating the <strong>Add Form</strong> button in the area above your post/page editor.', 'visual-form-builder' ); ?></p>
    	<p><?php _e( 'You may also manually insert the shortcode into a post/page.', 'visual-form-builder' ); ?></p>
    	<p>
    		<?php _e( 'Shortcode', 'visual-form-builder' ); ?>
    		<input value="[vfb id='<?php echo (int) $_GET['form']; ?>']" readonly="readonly" />
    	</p>
	<?php
	}
}
