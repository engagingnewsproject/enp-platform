<?php
   /*
   Plugin Name: Engaging Buttons
   Description: A plugin for giving respect to posts, pages, and comments.
   Version: 0.9
   Author: The Engaging News Project
   Author URI: http://engagingnewsproject.org
   License: GPLv3
   */

// Disallows this file to be accessed via a web browser
if ( ! defined( 'WPINC' ) ) {
    die;
}

// activate/deactivate cron jobs
register_activation_hook(__FILE__, 'enp_create_build_button_data_cron' );
register_deactivation_hook(__FILE__ , 'enp_remove_cron_jobs' );


define( 'ENP_BUTTON_ROOT_PATH', plugin_dir_path( __FILE__ ) );

include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Class.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Loader.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_User_Class.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Popular_Button_Class.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Send_Data_API.php');

//Automatically Load all the PHP files we need
$classesDir = array (
    plugin_dir_path( __FILE__ ) .'admin/functions/',
    plugin_dir_path( __FILE__ ) .'admin/settings/',
    plugin_dir_path( __FILE__ ) .'admin/widgets/',
    plugin_dir_path( __FILE__ ) .'front-end/functions/',
);

enp_button_include_files($classesDir);


add_action( 'template_redirect', function() {

  //Automatically Load all the PHP files we need
  $classesDir = array (
      plugin_dir_path( __FILE__ ) .'front-end/popular_buttons/',
  );

  enp_button_include_files($classesDir);

});


function enp_button_include_files($classesDir) {
  foreach ($classesDir as $directory) {
      foreach (glob($directory."*.php") as $filename){
          include $filename;
      }
  }
}


?>
