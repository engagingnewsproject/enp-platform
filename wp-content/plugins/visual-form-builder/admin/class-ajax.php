<?php
/**
 * Class that handles all AJAX calls
 *
 * @since 3.0
 */
class Visual_Form_Builder_Admin_AJAX {
	/**
	 * [__construct description]
	 */
	public function __construct() {
		add_action( 'wp_ajax_visual_form_builder_sort_field', array( $this, 'sort_field' ) );
		add_action( 'wp_ajax_visual_form_builder_create_field', array( $this, 'create_field' ) );
		add_action( 'wp_ajax_visual_form_builder_delete_field', array( $this, 'delete_field' ) );
		add_action( 'wp_ajax_visual_form_builder_form_settings', array( $this, 'form_settings' ) );
	}

	/**
	 * Sort fields
	 * @return [type] [description]
	 */
	public function sort_field() {
		global $wpdb;

		$data = array();

		foreach ( $_POST['order'] as $k ) {
			if ( 'root' !== $k['item_id'] && !empty( $k['item_id'] ) ) {
				$data[] = array(
					'field_id' => $k['item_id'],
					'parent'   => $k['parent_id']
				);
			}
		}

		foreach ( $data as $k => $v ) {
			// Update each field with it's new sequence and parent ID
			$wpdb->update( VFB_WP_FIELDS_TABLE_NAME, array(
				'field_sequence' => $k,
				'field_parent'   => $v['parent'] ),
				array(
					'field_id' => $v['field_id']
				),
				'%d'
			);
		}

		die(1);
	}

	/**
	 * Create field by click
	 * @return [type] [description]
	 */
	public function create_field() {
		global $wpdb;

		$data = array();
		$field_options = $field_validation = '';

		foreach ( $_POST['data'] as $k ) {
			$data[ $k['name'] ] = $k['value'];
		}

		check_ajax_referer( 'create-field-' . $data['form_id'], 'nonce' );

		$form_id 	= absint( $data['form_id'] );
		$field_key 	= sanitize_title( $_POST['field_type'] );
		$field_name = esc_html( $_POST['field_type'] );
		$field_type = strtolower( sanitize_title( $_POST['field_type'] ) );

		// Set defaults for validation
		switch ( $field_type ) {
			case 'select' :
			case 'radio' :
			case 'checkbox' :
				$field_options = serialize( array( 'Option 1', 'Option 2', 'Option 3' ) );
				break;

			case 'email' :
			case 'url' :
			case 'phone' :
				$field_validation = $field_type;
				break;

			case 'currency' :
				$field_validation = 'number';
				break;

			case 'number' :
				$field_validation = 'digits';
				break;

			case 'time' :
				$field_validation = 'time-12';
				break;

			case 'file-upload' :
				$field_options = serialize( array( 'png|jpe?g|gif' ) );
				break;
		}


		// Get the last row's sequence that isn't a Verification
		$sequence_last_row = $wpdb->get_var( $wpdb->prepare( "SELECT field_sequence FROM " . VFB_WP_FIELDS_TABLE_NAME . " WHERE form_id = %d AND field_type = 'verification' ORDER BY field_sequence DESC LIMIT 1", $form_id ) );

		// If it's not the first for this form, add 1
		$field_sequence = ( !empty( $sequence_last_row ) ) ? $sequence_last_row : 0;

		$newdata = array(
			'form_id' 			=> $form_id,
			'field_key' 		=> $field_key,
			'field_name' 		=> $field_name,
			'field_type' 		=> $field_type,
			'field_options' 	=> $field_options,
			'field_sequence' 	=> $field_sequence,
			'field_validation' 	=> $field_validation
		);

		// Create the field
		$wpdb->insert( VFB_WP_FIELDS_TABLE_NAME, $newdata );
		$insert_id = $wpdb->insert_id;

		// VIP fields
		$vip_fields = array( 'verification', 'secret', 'submit' );

		// Move the VIPs
		foreach ( $vip_fields as $update ) {
			$field_sequence++;
			$where = array(
				'form_id' 		=> absint( $data['form_id'] ),
				'field_type' 	=> $update
			);
			$wpdb->update( VFB_WP_FIELDS_TABLE_NAME, array( 'field_sequence' => $field_sequence ), $where );

		}

		$field = new Visual_Form_Builder_Admin_Fields();
		echo $field->field_output( $data['form_id'], $insert_id );

		die(1);
	}

	/**
	 * Delete field
	 * @return [type] [description]
	 */
	public function delete_field() {
		global $wpdb;

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'visual_form_builder_delete_field' ) {
			$form_id = absint( $_POST['form'] );
			$field_id = absint( $_POST['field'] );

			check_ajax_referer( 'delete-field-' . $form_id, 'nonce' );

			if ( isset( $_POST['child_ids'] ) ) {
				foreach ( $_POST['child_ids'] as $children ) {
					$parent = absint( $_POST['parent_id'] );

					// Update each child item with the new parent ID
					$wpdb->update( VFB_WP_FIELDS_TABLE_NAME, array( 'field_parent' => $parent ), array( 'field_id' => $children ) );
				}
			}

			// Delete the field
			$wpdb->query( $wpdb->prepare( "DELETE FROM " . VFB_WP_FIELDS_TABLE_NAME . " WHERE field_id = %d", $field_id ) );
		}

		die(1);
	}

	/**
	 * Form settings
	 * @return [type] [description]
	 */
	public function form_settings() {
		$current_user = wp_get_current_user();

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'visual_form_builder_form_settings' ) {
			$form_id 	= absint( $_POST['form'] );
			$status 	= isset( $_POST['status'] ) ? $_POST['status'] : 'opened';
			$accordion 	= isset( $_POST['accordion'] ) ? $_POST['accordion'] : 'general-settings';
			$user_id    = $current_user instanceof WP_User ? $current_user->ID : 1;

			$form_settings = get_user_meta( $user_id, 'vfb-form-settings', true );

			$array = array(
				'form_setting_tab' 	=> $status,
				'setting_accordion' => $accordion
			);

			// Set defaults if meta key doesn't exist
			if ( !$form_settings || $form_settings == '' ) {
				$meta_value[ $form_id ] = $array;

				update_user_meta( $user_id, 'vfb-form-settings', $meta_value );
			}
			else {
				$form_settings[ $form_id ] = $array;

				update_user_meta( $user_id, 'vfb-form-settings', $form_settings );
			}
		}

		die(1);
	}
}
