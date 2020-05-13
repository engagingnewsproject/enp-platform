<?php
/**
 * Class that displays all Admin Notices after saving
 *
 */
class Visual_Form_Builder_Admin_Notices {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'create_form' ) );
		add_action( 'admin_notices', array( $this, 'save_form' ) );
		add_action( 'admin_notices', array( $this, 'delete_form' ) );
		add_action( 'admin_notices', array( $this, 'copy_form' ) );
		add_action( 'admin_notices', array( $this, 'settings' ) );
	}

	/**
	 * Create Form
	 *
	 * @access public
	 * @return void
	 */
	public function create_form() {
		if ( !$this->submit_check() )
			return;

		if ( 'create_form' !== $_POST['action'] )
			return;

		echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'Form created.' , 'visual-form-builder' ) );
	}

	/**
	 * Save Form
	 *
	 * @access public
	 * @return void
	 */
	public function save_form() {
		if ( !$this->submit_check() )
			return;

		if ( 'update_form' !== $_POST['action'] )
			return;

		echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'Form updated.' , 'visual-form-builder' ) );
	}

	/**
	 * Delete Form
	 *
	 * @access public
	 * @return void
	 */
	public function delete_form() {
		if ( !$this->submit_check() )
			return;

		if ( 'deleted' !== $_POST['action'] )
			return;

		echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'Item permanently deleted.' , 'visual-form-builder' ) );
	}

	/**
	 * Copy Form
	 *
	 * @access public
	 * @return void
	 */
	public function copy_form() {
		if ( !$this->submit_check() )
			return;

		if ( 'copy_form' !== $_POST['action'] )
			return;

		echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'Item successfully duplicated.' , 'visual-form-builder' ) );
	}

	/**
	 * Settings page
	 *
	 * @access public
	 * @return void
	 */
	public function settings() {
		if ( !$this->submit_check() )
			return;

		if ( 'vfb_settings' !== $_POST['action'] )
			return;

		echo sprintf( '<div id="message" class="updated"><p>%s</p></div>', __( 'Settings saved.' , 'visual-form-builder' ) );
	}

	/**
	 * Basic check to exit if the form hasn't been submitted
	 *
	 * @access public
	 * @return void
	 */
	public function submit_check() {
		if ( !isset( $_POST['action'] ) || !isset( $_GET['page'] ) )
			return;

		$pages = array(
			'visual-form-builder',
			'vfb-add-new',
			'vfb-entries',
			'vfb-export',
			'vfb-settings',
		);

		if ( !in_array( $_GET['page'], $pages ) )
			return;

		return true;
	}
}
