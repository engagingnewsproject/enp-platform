<?php
   /*
   Plugin Name: Engaging Button
   Description: A plugin for giving respect to posts, pages, and comments.
   Version: 0.0.4
   Author: Engaging News Project
   Author URI: http://engagingnewsproject.org
   License: ASK US
   */

// Disallows this file to be accessed via a web browser
if ( ! defined( 'WPINC' ) ) {
    die;
}


register_activation_hook(__FILE__, 'enp_create_cron_jobs' );
register_deactivation_hook(__FILE__ , 'enp_remove_cron_jobs' );


define( 'ENP_BUTTON_ROOT_PATH', plugin_dir_path( __FILE__ ) );

include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Class.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Loader.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_User_Class.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Popular_Class.php');

//Automatically Load all the PHP files we need
$classesDir = array (
    plugin_dir_path( __FILE__ ) .'admin/functions/',
    plugin_dir_path( __FILE__ ) .'admin/settings/',
    plugin_dir_path( __FILE__ ) .'front-end/functions/',
);

foreach ($classesDir as $directory) {
    foreach (glob($directory."*.php") as $filename){
        include $filename;
    }
}


?>
