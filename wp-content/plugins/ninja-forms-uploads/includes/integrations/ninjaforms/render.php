<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NF_FU_Integrations_NinjaForms_Render {


	/**
	 * NF_FU_Integrations_NinjaForms_Render constructor.
	 */
	public function __construct() {
		add_filter( 'nf_fu_enqueue_scripts', array( $this, 'maybe_enqueue_scripts'), 10, 3 );
	}

	/**
	 * Ensure the File Upload scripts are enqueued for Repeater fields with File Upload subfields.
	 *
	 * @param bool         $load
	 * @param object|array $field
	 *
	 * @return bool
	 */
	public function maybe_enqueue_scripts( $load, $field ) {
		if ( $load ) {
			return $load;
		}

		if ( $field['settings']['type'] !== 'repeater' || ! class_exists( 'NF_Display_Render' ) ) {
			return $load;
		}

		return NF_Display_Render::checkRepeaterChildType( $field, 'file_upload' );
	}
}

