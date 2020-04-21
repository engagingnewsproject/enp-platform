<?php
/*
Plugin Name: 	Visual Form Builder
Plugin URI:		https://wordpress.org/plugins/visual-form-builder/
Description: 	Dynamically build forms using a simple interface. Forms include jQuery validation, a basic logic-based verification system, and entry tracking.
Version: 		3.0.1
Author:			Matthew Muro
Author URI: 	http://vfbpro.com
Text Domain: 	visual-form-builder
Domain Path:	/languages/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) )
	exit;

class Visual_Form_Builder {

	/**
	 * The unique identifier of this plugin.
	 * @var [type]
	 */
	protected $plugin_name = 'visual-form-builder';

	/**
	 * The current version of the plugin.
	 * @var [type]
	 */
	protected $version = '3.0.1';

	/**
	 * The current DB version. Used if we need to update the DB later.
	 * @var [type]
	 */
	protected $db_version = '2.9';

	/**
	 * The main instance of Visual_Form_Builder
	 * @var [type]
	 */
	private static $instance = null;

	/**
     * Protected constructor to prevent creating a new instance of Visual_Form_Builder
     * via the 'new' operator from outside of this class.
     *
     * @return void
     */
	protected function __construct() {
	}

	/**
     * Private clone method to prevent cloning of the instance.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing of the instance.
     *
     * @return void
     */
    private function __wakeup() {
    }

	/**
	 * Create a single Visual_Form_Builder instance
	 *
	 * Insures that only one instance of Visual_Form_Builder is running.
	 * Otherwise known as the Singleton class pattern
	 *
	 * @since    3.0
	 * @access   public
	 * @static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new Visual_Form_Builder;
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->autoload_classes();

			// Setup Entries CPT
			//self::$instance->entries_cpt = new VFB_Pro_Entries_CPT();

			// Install DB
			register_activation_hook( __FILE__, array( self::$instance, 'install' ) );

			// Update DB
			add_action( 'plugins_loaded', array( self::$instance, 'upgrade_db_check' ) );

			// Load i18n
			add_action( 'plugins_loaded', array( self::$instance, 'lang' ) );

			$screen_options = new Visual_Form_Builder_Admin_Screen_Options();
			add_filter( 'set-screen-option', array( $screen_options, 'save_option' ), 10, 3 );
		}

		return self::$instance;
	}

	/**
	 * Setup constants
	 * @return [type] [description]
	 */
	private function setup_constants() {
		global $wpdb;

		// Database version
		if ( !defined( 'VFB_WP_DB_VERSION' ) )
			define( 'VFB_WP_DB_VERSION', $this->db_version );

		// Plugin version
		if ( !defined( 'VFB_WP_PLUGIN_VERSION' ) )
			define( 'VFB_WP_PLUGIN_VERSION', $this->version );

		// Plugin Folder Path
		if ( !defined( 'VFB_WP_PLUGIN_DIR' ) )
			define( 'VFB_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		// Plugin Folder URL
		if ( !defined( 'VFB_WP_PLUGIN_URL' ) )
			define( 'VFB_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		// Plugin Root File
		if ( !defined( 'VFB_WP_PLUGIN_FILE' ) )
			define( 'VFB_WP_PLUGIN_FILE', __FILE__ );

		// Form table name
		if ( !defined( 'VFB_WP_FORMS_TABLE_NAME' ) )
			define( 'VFB_WP_FORMS_TABLE_NAME', $wpdb->prefix . 'visual_form_builder_forms' );

		// Field table name
		if ( !defined( 'VFB_WP_FIELDS_TABLE_NAME' ) )
			define( 'VFB_WP_FIELDS_TABLE_NAME', $wpdb->prefix . 'visual_form_builder_fields' );

		// Form meta table name
		if ( !defined( 'VFB_WP_ENTRIES_TABLE_NAME' ) )
			define( 'VFB_WP_ENTRIES_TABLE_NAME', $wpdb->prefix . 'visual_form_builder_entries' );
	}

	/**
	 * Include files
	 * @return [type] [description]
	 */
	private function includes() {
		require_once( VFB_WP_PLUGIN_DIR . 'inc/class-install.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'inc/class-uninstall.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'inc/class-i18n.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'inc/class-list-table.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-admin-menu.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-admin-notices.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-screen-options.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-media-button.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-dashboard-widgets.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-widget.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-load-css-js.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-entries-detail.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-entries-list.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-forms-list.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-forms-new.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-forms-edit.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-forms-metaboxes.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-fields.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-page-settings.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-ajax.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-save.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'admin/class-export.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'public/class-form-display.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'public/class-load-css-js.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'public/class-confirmation.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'public/class-email.php' );
		require_once( VFB_WP_PLUGIN_DIR . 'public/class-security.php' );
	}

	/**
	 * Install DB
	 * @return [type] [description]
	 */
	public function install() {
		$install = new Visual_Form_Builder_Install();
		$install->install();
	}

	/**
	 * Check database version and run SQL install, if needed
	 * @return [type] [description]
	 */
	public function upgrade_db_check() {
		$current_db_version = VFB_WP_DB_VERSION;

		if ( get_option( 'vfb_db_version' ) != $current_db_version ) {
			$install = new Visual_Form_Builder_Install();
			$install->install_db();
		}
	}

	/**
	 * Load localization file
	 * @return [type] [description]
	 */
	public function lang() {
		$i18n = new Visual_Form_Builder_i18n();
		$i18n->set_domain( $this->plugin_name );

		$i18n->load_lang();
	}

	/**
	 * Autoload some VFB classes that aren't loaded via other files.
	 * @return [type] [description]
	 */
	public function autoload_classes() {
		$admin_menu        = new Visual_Form_Builder_Admin_Menu();
		$admin_ajax        = new Visual_Form_Builder_Admin_AJAX();
		$admin_save        = new Visual_Form_Builder_Admin_Save();
		$admin_notices     = new Visual_Form_Builder_Admin_Notices();
		$dashboard_widgets = new Visual_Form_Builder_Dashboard_Widgets();
		$export            = new Visual_Form_Builder_Export();
		$media_button      = new Visual_Form_Builder_Media_Button();

		Visual_Form_Builder_Form_Display::instance();
	}
}

/**
 * The main function responsible for returning Visual Form Builder forms and functionality.
 * @return [type] [description]
 */
function visual_form_builder_plugin_instance() {
	return Visual_Form_Builder::instance();
}

visual_form_builder_plugin_instance();
