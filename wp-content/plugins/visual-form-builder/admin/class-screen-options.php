<?php
/**
 * Handle Screen Options
 *
 * Defines and saves all options in Screen Options tabs
 *
 */
class Visual_Form_Builder_Admin_Screen_Options {

	/**
	 * Add options to Screen Options
	 *
	 * @access public
	 * @return void
	 */
	public function add_option() {
		if ( isset( $_GET['form'] ) ) {
			add_screen_option( 'layout_columns', array(
				'max'		=> 2,
				'default'	=> 2
			) );
		} else {
			add_screen_option( 'per_page', array(
				'label'		=> __( 'Forms per page', 'visual-form-builder' ),
				'default'	=> 20,
				'option'	=> 'vfb_forms_per_page'
			) );
		}
	}

	/**
	 * Add options to Entries page
	 */
	public function add_option_entries() {
		add_screen_option( 'per_page', array(
			'label'		=> __( 'Entries per page', 'visual-form-builder' ),
			'default'	=> 20,
			'option'	=> 'vfb_entries_per_page'
		) );
	}

	/**
	 * Save Screen Options
	 *
	 * @access public
	 * @param mixed $status		Return this so we don't break other plugins
	 * @param mixed $option		The option name
	 * @param mixed $value		The submitted value
	 * @return void
	 */
	public function save_option( $status, $option, $value ) {

		if ( 'vfb_forms_per_page' == $option )
			return $value;

		if ( 'vfb_entries_per_page' == $option )
			return $value;

		return $status;
	}
}
