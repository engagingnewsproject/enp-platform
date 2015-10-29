<?php
   /*
   Plugin Name: Engaging Button
   Description: A plugin for giving respect to posts, pages, and comments.
   Version: 0.0.2
   Author: Engaging News Project
   Author URI: http://engagingnewsproject.org
   License: ASK US
   */

// Disallows this file to be accessed via a web browser
if ( ! defined( 'WPINC' ) ) {
    die;
}

include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Class.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_Loader.php');
include(plugin_dir_path( __FILE__ ) .'inc/Enp_Button_User_Class.php');

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
