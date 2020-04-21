<?php

/**
 * Loads all CSS and JS files that VFB needs
 *
 * This class should be called when the menu is added
 * so the CSS and JS is added to ONLY our VFB pages.
 *
 */
class Visual_Form_Builder_Admin_Scripts_Loader {
	/**
	 * Load CSS on VFB admin pages.
	 *
	 * Called from the Visual_Form_Builder_Admin_Menu class
	 *
	 * @access public
	 * @return void
	 */
	public function add_css() {
		wp_enqueue_style( 'visual-form-builder-style', VFB_WP_PLUGIN_URL . "admin/assets/css/visual-form-builder-admin.min.css", array(), '2019.04.01' );
	}

	/**
	 * Load JS on VFB admin pages
	 *
	 * Called from the Visual_Form_Builder_Admin_Menu class
	 *
	 * @access public
	 * @return void
	 */
	public function add_js() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'jquery-form-validation', VFB_WP_PLUGIN_URL . 'admin/assets/js/jquery.validate.min.js', array( 'jquery' ), '1.9.0', true );
		wp_enqueue_script( 'vfb-admin', VFB_WP_PLUGIN_URL . 'admin/assets/js/vfb-admin.min.js', array( 'jquery', 'jquery-form-validation' ), '20140412', true );
		wp_enqueue_script( 'nested-sortable', VFB_WP_PLUGIN_URL . 'admin/assets/js/jquery.ui.nestedSortable.min.js', array( 'jquery', 'jquery-ui-sortable' ), '1.3.5', true );
	}
}
