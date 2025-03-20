<?php
/*
Plugin Name: Export Import Menus
Plugin URI: https://in.linkedin.com/in/akshay-menariya-5218a664
Description: Plugin to export and import WordPress Menus. This plugin also support UberMenu plugin.
Author: Akshay Menariya
Version: 1.9.2
Author URI: https://profiles.wordpress.org/akshay-menariya
*/
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
register_activation_hook(   __FILE__, array( 'DspExportImportMenus', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'DspExportImportMenus', 'on_deactivation' ) );
register_uninstall_hook(    __FILE__, array( 'DspExportImportMenus', 'on_uninstall' ) );
include ("constants.php");

	if ( ! class_exists( "DspExportImportMenus" ) ):
	class DspExportImportMenus
	{
			protected static $instance;
			var $controller = null;
			var $error = false;
		
			public static function init()
			{
						is_null( self::$instance ) AND self::$instance = new self;
						return self::$instance;
			}
		
			public static function on_activation()
			{
							if ( ! current_user_can( 'activate_plugins' ) )
							return;
			}
	    
			public static function on_deactivation()
			{
							if ( ! current_user_can( 'activate_plugins' ) )
							return;						
			}
	    
			public static function on_uninstall()
			{           
							if ( ! current_user_can( 'activate_plugins' ) ||  __FILE__ != 'WP_UNINSTALL_PLUGIN')
							return;	
							check_admin_referer( 'bulk-plugins' );
							if ( __FILE__ != 'WP_UNINSTALL_PLUGIN' )
							return;    
			}
		
			public function __construct()
			{
							if( is_admin() )
							{
										$this->dspIncludes();
										$this->dspExportImportAjax();
										if(isset($_POST['menu']) && $_POST['menu'] != '' && isset($_POST['dspmenustask']) && $_POST['dspmenustask'] == 'dspExportMenus')
										{
											add_action( 'init',array( $this, 'dspDownloadJson' ) );
										}
										else
										{
											add_action( 'admin_menu',array( $this, 'createAdminMenu' ) );
											add_action( 'admin_enqueue_scripts',array( $this, 'dspEnqueueJs') );
											add_action( 'admin_enqueue_scripts',array( $this, 'dspEnqueueCss') );
											add_action( 'admin_notices',  array( $this, 'dspExportMenusError' ) );
										}
							}
			}
			
/**
	 * Used to Enqueue the javascript  
*/
			public function dspEnqueueJs()
			{
				$handle_adminjs = 'DspExportImportScript.js';
				$list = 'enqueued';
				if (!wp_script_is( $handle_adminjs, $list ))
				{
							wp_register_script( 'dspexportmenus', DSPMENUS_URL.'assets/DspExportImportScript.js');
							wp_localize_script( 'dspexportmenus', 'dspexportmenus', array( 'ajaxurl' => admin_url( 'admin-ajax.php') , 'nonce_verify' => wp_create_nonce( 'menus_nonce_verify') ) );
							wp_enqueue_script( 'dspexportmenus' );
				}		
			}
			
/**
	 * Used to Enqueue the CSS  
*/
		public function dspEnqueueCss()
		{
			$handle_admincss = 'DspExportImportCss.css';				
			$list = 'enqueued';
			if ( ! wp_style_is( $handle_admincss, $list ))
			wp_enqueue_style('dsp-exportimport-css', DSPMENUS_URL.'assets/DspExportImportCss.css');
		}
		
		
			private function dspIncludes()
			{
						require_once DSPMENUS_DIR . 'helpers/DspHelper.php';
						require_once DSPMENUS_DIR . 'controllers/DspExportImportController.php';
						require_once DSPMENUS_DIR . 'models/DspExportImportModel.php';
			}
		
/**
	* Used for Creating the Menu  under the Appearence Menu in Wordpress Admin
*/
			public function createAdminMenu()
			{
						add_theme_page( 'Export/Import Menus', 'Export/Import Menus', 'edit_theme_options', 'dsp_export_import_menus',  array($this,'createListMenus'));
			}

/**
	* Call back function of the function createAdminMenu() 
*/
			public function createListMenus()
			{
						$this->dspMenusController();
			}

/**
	* Function call For the Ajax of Importing 
*/
			public function dspExportImportAjax(){
					 add_action( 'wp_ajax_dspImportMenus',array($this,'dspMenusController'));
			}

/**
	*  Function call For creating the object of Controller 
*/	
			public function dspMenusController($task=null)
			{
				if ( current_user_can( 'edit_theme_options' ) ) {
					if(isset($_POST["dspmenustask"])){
						$task=$_POST["dspmenustask"];				
					}else{
						$task="listMenus";				
					}
					$settings = new DspExportImportController($task);
				}
			}
			
/**
	*  To Handle the Errors  
*/			
			public static function dspExportMenusError($status=null)
			{
		   if(isset($_GET['page']) && $_GET['page'] == 'dsp_export_import_menus' && isset($_POST['menu']) && $_POST['menu'] =='')
					{
				?>
							<div class="error notice">
									<p><?php _e( DSPMENUS_EXPORTERR1, 'DspExportImportMenus' ); ?></p>
							</div>
						<?php
					}
			}

/**
	*  To Download the JSON File
	*  @param $requested_vars accepts the $_POST values of the Import Form
*/			
			public static function dspDownloadJson($requested_vars=null)
			{
						$modelObj = new DspExportImportModel();
						$modelObj->generateMenusJson($_POST);
			}
			
	}//end of class
$dspExportObj = new DspExportImportMenus();
endif;