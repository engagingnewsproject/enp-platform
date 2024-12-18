<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use NinjaForms\FileUploads\Common\Factories\LoggerFactory;
use NinjaForms\FileUploads\Common\Routes\DebugLog as DebugLogRoutes;
/**
 * Class NF_FU_File_Uploads
 */
final class NF_FU_File_Uploads {

	/**
	 * @var NF_FU_File_Uploads
	 */
	private static $instance;

	/**
	 * @var string
	 */
	public $plugin_file_path;

	/**
	 * @var string
	 */
	public $plugin_name;

	/**
	 * @var stdClass
	 */
	public $controllers;

	/**
	 * @var NF_FU_Integrations_NinjaForms_MergeTags
	 */
	public $mergetags;

	/**
	 * @var NF_FU_Database_Models_Upload
	 */
	public $model;

	/**
	 * @var NF_FU_External_Loader
	 */
	public $externals;

	/**
	 * @var NF_FU_Admin_Menus_Uploads
	 */
	public $page;

	/**
	 * @var string
	 */
	protected $plugin_option_prefix;

	/**
	 * @var string
	 */
	public $plugin_version;

	/**
	 * @var string
	 */
	protected $class_prefix;

	/**
	 * File Upload field type
	 */
	const TYPE = 'file_upload';

	/**
     * Route for debug log REST requests
     *
     * @var string
     */
    protected $debugLogSlug= 'nf-file-uploads';
	
	/** @var LoggerFactory */
	protected $loggerFactory;

	/**
	 * Main Plugin Instance
	 *
	 * Insures that only one instance of a plugin class exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @param string $plugin_file_path
	 * @param string $plugin_version
	 *
	 * @return NF_FU_File_Uploads Instance
	 */
	public static function instance( $plugin_file_path, $plugin_version ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof NF_FU_File_Uploads ) ) {
			self::$instance = new NF_FU_File_Uploads();

			spl_autoload_register( array( self::$instance, 'autoloader' ) );

			// Initialize the class
			self::$instance->init( $plugin_file_path, $plugin_version );
		}

		return self::$instance;
	}

	/**
	 * Initialize the class.
	 *
	 * @param string $plugin_file_path
	 * @param string $plugin_version
	 */
	protected function init( $plugin_file_path, $plugin_version ) {
		$this->plugin_file_path     = $plugin_file_path;
		$this->plugin_name          = 'File Uploads';
		$this->plugin_option_prefix = 'uploads';
		$this->plugin_version       = $plugin_version;
		$this->class_prefix         = 'NF_FU';

		add_action( 'admin_init', array( $this, 'setup_license' ) );

		// Import Form Upgrade Routine for 3.0
		new NF_FU_Admin_Upgrade();

		// This is THREE!
		add_filter( 'ninja_forms_register_fields', array( $this, 'register_field' ) );
		add_filter( 'ninja_forms_field_template_file_paths', array( $this, 'register_template_path' ) );
		add_action( 'ninja_forms_loaded', array( $this, 'load_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'load_translations' ) );
		add_action('plugins_loaded', array($this, 'initializeLogger'));
		add_action('init', [$this, 'registerSettingsScript']);
		add_action( 'ninja_forms_rollback', array( $this, 'handle_rollback' ) );
		add_filter( 'ninja_forms_telemetry_should_send', '__return_true' );

		// External services
		self::$instance->externals = new NF_FU_External_Loader();

		// Integrations
		self::$instance->mergetags = new NF_FU_Integrations_NinjaForms_MergeTags();
		new NF_FU_Integrations_NinjaForms_Submissions();
		new NF_FU_Integrations_NinjaForms_Attachments();
		new NF_FU_Integrations_NinjaForms_Templates();
		new NF_FU_Integrations_NinjaForms_Builder();
		new NF_FU_Integrations_NinjaForms_Render();
		new NF_FU_Integrations_PostCreation_PostCreation();
		new NF_FU_Integrations_SaveProgress_SaveProgress();
		new NF_FU_Integrations_Zapier_Zapier();
		new NF_FU_Integrations_PdfSubmissions_PdfSubmissions();
		if ( class_exists( 'NF_Styles' ) ) {
			new NF_FU_Integrations_LayoutStyles_LayoutStyles();
		}

		self::$instance->controllers               = new stdClass();
		self::$instance->controllers->settings     = new NF_FU_Admin_Controllers_Settings();
		self::$instance->controllers->custom_paths = new NF_FU_Admin_Controllers_CustomPaths();
		self::$instance->controllers->uploads      = new NF_FU_Admin_Controllers_Uploads();
	}

	/**
	 * Load all the 3.0+ plugin code
	 */
	public function load_plugin() {
		$this->install();

		$ajax_upload = new NF_FU_AJAX_Controllers_Uploads();
		$ajax_upload->init();


		self::$instance->model = new NF_FU_Database_Models_Upload();

		self::$instance->page = new NF_FU_Admin_Menus_Uploads();
		new NF_FU_Display_Render();

		Ninja_Forms()->merge_tags[ 'file_uploads' ] = new NF_FU_Integrations_NinjaForms_FileUploadMergeTags();
	}

	/**
	 * Register field
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function register_field( $fields ) {
		$fields[ self::TYPE ] = new NF_FU_Fields_Upload();

		return $fields;
	}

	/**
	 * Register the template path for the plugin
	 *
	 * @param array $file_paths
	 *
	 * @return array
	 */
	public function register_template_path( $file_paths ) {
		$file_paths[] = dirname( $this->plugin_file_path ) . '/includes/templates/';

		return $file_paths;
	}

	/**
	 * Install plugin
	 */
	public function install() {
		$migrations = new NF_FU_Database_Migrations();
		$migrations->migrate();
	}

	public function initializeLogger(): void
	{
		if (class_exists('Ninja_Forms')) {
			$debugLog = \Ninja_Forms()->get_setting('file_uploads_turn_on_debug_logger');
		} else {
			$debugLog = false;
		}

		$isDebugOn = (bool)$debugLog;

		$this->loggerFactory = new LoggerFactory($this->debugLogSlug, $isDebugOn);

		$this->loggerFactory->createDebugLogRoutes($this->debugLogSlug);

		if ($isDebugOn) {
			add_filter('ninja_forms_admin_notices', array($this, 'adminNotices'));
		}

		$logger = $this->loggerFactory->getLogger();
		self::$instance->externals->setLogger($logger);
	}

	/**
	 * Function to register any admin notices we need to show.
	 * 
	 * @param $notices (Array) The list of admin notices.
	 * @return array The updated list of admin notices.
	 * 
	 */
	public function adminNotices($notices)
	{
		// Register an admin notice.
		$notices['file_uploads_turn_on_debug_logger'] = array(
			'title' => __('Ninja Forms File Uploads Debug Logger', 'ninja-forms-uploads'),
			'msg' => sprintf(__('%sThe debug logger records data to help solve issues, but should be turned off as soon as you capture enough information.%s', 'ninja-forms-uploads'), '<p>', '</p>'),
			'int' => 0,
			'ignore_spam' => true,
			'dismiss' => 1
		);

		return $notices;
	}

	public function registerSettingsScript(): void
	{
	  $handle = 'file_uploads_nfpluginsettings';
  
	  $scriptUrl = plugin_dir_url(__DIR__).'assets/js/nfpluginsettings.js';
  
	  $objectName = 'params';
  
	  $localizedArray = [
		'clearLogRestUrl'=>\rest_url().$this->debugLogSlug.'/'.DebugLogRoutes::DELETELOGSENDPOINT,
		'clearLogButtonId'=>'file_uploads_clear_debug_logger',
		'downloadLogRestUrl'=>\rest_url().$this->debugLogSlug.'/'.DebugLogRoutes::GETLOGSENDPOINT,
		'downloadLogButtonId'=>'file_uploads_download_debug_logger',
				  
	  ];
  
	  //Register asset 
	  \wp_enqueue_script(
		$handle,
		$scriptUrl,
		['jquery'],
		$this->plugin_version
	  );
  
  
	  \wp_localize_script(
		$handle,
		$objectName,
		$localizedArray
	  );
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * class via the `new` operator from outside of this class.
	 */
	protected function __construct() {
	}

	/**
	 * As this class is a singleton it should not be clone-able
	 */
	protected function __clone() {
	}

	/**
	 * As this class is a singleton it should not be able to be unserialized
	 */
	public function __wakeup() {
	}

	/**
	 * Autoload the classes
	 *
	 * @param string $class_name
	 */
	public function autoloader( $class_name ) {
		if ( class_exists( $class_name ) ) {
			return;
		}

		$classes_dir = realpath( plugin_dir_path( $this->plugin_file_path ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
		
		if(strpos($class_name,'NinjaForms\\FileUploads\\')===0){
			$unprefixed = str_replace('NinjaForms\\FileUploads\\','',$class_name);
			$this->loadNamespacedClass($unprefixed,$classes_dir);
			return;
		}

		$this->maybe_load_class( $class_name, $this->class_prefix, $classes_dir );
	}

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadNamespacedClass($class_name,$dir)
    {
		$class_file = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name ) . '.php';

		if ( file_exists( $dir . $class_file ) ) {
			require_once $dir . $class_file;
		}
    }

	/**
	 * Load class file
	 *
	 * @param string $class
	 * @param string $prefix
	 * @param string $dir
	 * @param bool   $preserve_case
	 */
	public function maybe_load_class( $class, $prefix, $dir, $preserve_case = false ) {
		if ( false === strpos( $class, $prefix ) ) {
			return;
		}

		$class_name = str_replace( $prefix, '', $class );
		$class_name = $preserve_case ? $class_name : strtolower( $class_name );
		$class_file = str_replace( '_', DIRECTORY_SEPARATOR, $class_name ) . '.php';

		if ( file_exists( $dir . $class_file ) ) {
			require_once $dir . $class_file;
		}
	}

	/**
	 * Licensing for the addon
	 */
	public function setup_license() {
		if ( ! class_exists( 'NF_Extension_Updater' ) ) {
			return;
		}

		new NF_Extension_Updater( $this->plugin_name, $this->plugin_version, 'WP Ninjas', $this->plugin_file_path, $this->plugin_option_prefix );
	}

	/**
	 * Config
	 *
	 * @param string $file_name
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function config( $file_name, $data = array() ) {
		extract( $data );

		return include dirname( $this->plugin_file_path ) . '/includes/config/' . $file_name . '.php';
	}

	/**
	 * Template
	 *
	 * @param string $file_name
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function template( $file_name, array $data = array() ) {
		extract( $data );

		$ext       = pathinfo( $file_name, PATHINFO_EXTENSION );
		$file_name = empty( $ext ) ? $file_name . '.php' : $file_name;

		return include dirname( $this->plugin_file_path ) . '/includes/templates/' . $file_name;
	}

	/**
	 * Load translations for add-on.
	 * First, look in WP_LANG_DIR subfolder, then fallback to add-on plugin folder.
	 */
	public function load_translations() {
		$textdomain = 'ninja-forms-uploads';

		$locale  = apply_filters( 'plugin_locale', get_locale(), $textdomain );
		$mo_file = $textdomain . '-' . $locale . '.mo';

		$wp_lang_dir = trailingslashit( WP_LANG_DIR ) . 'ninja-forms-uploads/';

		load_textdomain( $textdomain, $wp_lang_dir . $mo_file );

		$plugin_dir = trailingslashit( basename( dirname( $this->plugin_file_path ) ) );
		$lang_dir   = apply_filters( 'ninja_forms_uploads_lang_dir', $plugin_dir . 'languages/' );
		load_plugin_textdomain( $textdomain, false, $lang_dir );
	}

	/**
	 * Normalize the submission value for a file upload so we don't need to convert data
	 * and the plugin can use both formats in a pre and post 3.0 world
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	public function normalize_submission_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$clean_value = array();

		$first = reset( $value );
		if ( is_array( $first ) && isset( $first['user_file_name'] ) ) {
			foreach ( $value as $item ) {
				$clean_value[ $item['upload_id'] ] = $item['file_url'];
			}
		} else {
			$clean_value = $value;
		}

		return $clean_value;
	}

	/**
	 * Create a nonce for the field along with expiry timestamp.
	 *
	 * @param int $field_id
	 *
	 * @return array
	 */
	public function createFieldNonce( $field_id ) {
		$nonce = array(
			'nonce' => wp_create_nonce( 'nf-file-upload-' . $field_id ),
			'nonce_expiry' => time() + wp_nonce_tick(),
		);

		return $nonce;
	}

	/**
	 * Set default list of allowed File Types
	 *
	 * @return string of file extensions allowed
	 */
	public static function getDefaultTypesAllowed() {
		$wp_file_types = wp_get_ext_types();
		unset($wp_file_types["interactive"], $wp_file_types["archive"], $wp_file_types["code"]);
		$default_types_allowed = "doc,ppt,pptx,pps,ppsx,";
		foreach(array_values($wp_file_types) as $values){
			$default_types_allowed .= implode(",", $values).',';
		};

		$default_types_allowed = rtrim($default_types_allowed,',');
		
		return $default_types_allowed;
	}
}
