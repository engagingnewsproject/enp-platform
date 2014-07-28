<?php
/*
Plugin Name: XML Sitemap - XML-Sitemap.co.uk
Plugin URI: http://www.XML-Sitemap.co.uk
Description: XML Sitemap creates an XML for use with Google and Yahoo, you can also place a HTML sitemap using the shortcode [xml-sitemap]
Version: 0.1.0.0
Author: XML-Sitemap - XML-Sitemap.co.uk
Author URI: http://www.XML-Sitemap.co.uk
License: GPL2
*/

/*  Copyright 2011  http://www.XML-Sitemap.co.uk  (email : simon@xml-sitemap.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php

// some definition we will use
define( 'XMLS_PUGIN_NAME', 'XML Sitemap Generator');
define( 'XMLS_PLUGIN_DIRECTORY', 'xml-sitemap-xml-sitemapcouk');
define( 'XMLS_CURRENT_VERSION', '0.1.1.0' );
define( 'XMLS_CURRENT_BUILD', '3' );
define( 'XMLS_LOGPATH', str_replace('\\', '/', WP_CONTENT_DIR).'/xmls-logs/');
define( 'XMLS_DEBUG', false);		# never use debug mode on productive systems
// i18n plugin domain for language files
define( 'EMU2_I18N_DOMAIN', 'XMLS' );

// how to handle log files, don't load them if you don't log
require_once('XMLS_logfilehandling.php');

// load language files
function XMLS_set_lang_file() {
	# set the language file
	$currentLocale = get_locale();
	if(!empty($currentLocale)) {
		$moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
		if (@file_exists($moFile) && is_readable($moFile)) {
			load_textdomain(EMU2_I18N_DOMAIN, $moFile);
		}

	}
}
XMLS_set_lang_file();

// create custom plugin settings menu
add_action( 'admin_menu', 'XMLS_create_menu' );

//call register settings function
add_action( 'admin_init', 'XMLS_register_settings' );
add_shortcode( 'xml-sitemap', 'xmls_func' );

add_action("publish_post", "XMLS_create_sitemap");
add_action("publish_page", "XMLS_create_sitemap");

function XMLS_create_sitemap() {
  $postsForSitemap = get_posts(array(
    'numberposts' => -1,
    'orderby' => 'modified',
    'post_type'  => array('post','page'),
    'order'    => 'DESC'
  ));
  
  $sitemap = '<?xml version="1.0" encoding="UTF-8"?>
  <!--
Plugin Name: XML Sitemap - XML-Sitemap.co.uk
Plugin URI: http://www.XML-Sitemap.co.uk
Description: XML Sitemap creates an XML for use with Google and Yahoo, you can also place a HTML sitemap using the shortcode [xml-sitemap]
Version: 0.1.0.0
Author: XML-Sitemap - XML-Sitemap.co.uk
Author URI: http://www.XML-Sitemap.co.uk
License: GPL2


 Copyright 2011  http://www.XML-Sitemap.co.uk  (email : simon@xml-sitemap.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
-->';
  $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  
  foreach($postsForSitemap as $post) {
    setup_postdata($post);
    
    $postdate = explode(" ", $post->post_modified);
    
    $sitemap .= '<url>'.
      '<loc>'. get_permalink($post->ID) .'</loc>'.
      '<lastmod>'. $postdate[0] .'</lastmod>'.
      '<changefreq>monthly</changefreq>'.
    '</url>';
  }
  
  $sitemap .= '</urlset>';
  
  $fp = fopen(ABSPATH . "sitemap.xml", 'w');
  fwrite($fp, $sitemap);
  fclose($fp);
}

register_activation_hook(__FILE__, 'XMLS_activate');
register_deactivation_hook(__FILE__, 'XMLS_deactivate');
register_uninstall_hook(__FILE__, 'XMLS_uninstall');

// activating the default values
function XMLS_activate() {
	add_option('XMLS_option_3', 'any_value');
}

// deactivating
function XMLS_deactivate() {
	// needed for proper deletion of every option
	delete_option('XMLS_option_3');
}

// uninstalling
function XMLS_uninstall() {
	# delete all data stored
	delete_option('XMLS_option_3');
	// delete log files and folder only if needed
	if (function_exists('XMLS_deleteLogFolder')) XMLS_deleteLogFolder();
}
function xmls_func(){
 
  wp_list_pages('sort_column=menu_order');

}

function XMLS_create_menu() {
    $postsForSitemap = get_posts(array(
    'numberposts' => -1,
    'orderby' => 'modified',
    'post_type'  => array('post','page'),
    'order'    => 'DESC'
    ));
    
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
    //$sitemap .= '<!-- Generated by XML Sitemap - http://www.XML-Sitemap.co.uk -->';
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    foreach($postsForSitemap as $post) {
    setup_postdata($post);
    
    $postdate = explode(" ", $post->post_modified);
    
    $sitemap .= '<url>'.
      '<loc>'. get_permalink($post->ID) .'</loc>'.
      '<lastmod>'. $postdate[0] .'</lastmod>'.
      '<changefreq>monthly</changefreq>'.
    '</url>';
    }
    
    $sitemap .= '</urlset>';
    
    $fp = fopen(ABSPATH . "sitemap.xml", 'w');
    fwrite($fp, $sitemap);
    fclose($fp);
	// create new top-level menu
	add_menu_page( 
	__('XML-Sitemap', EMU2_I18N_DOMAIN),
	__('XML-Sitemap', EMU2_I18N_DOMAIN),
	0,
	XMLS_PLUGIN_DIRECTORY.'/XMLS_settings_page.php',
	'',
	plugins_url('/images/icon.png', __FILE__));
	
	
	add_submenu_page( 
	XMLS_PLUGIN_DIRECTORY.'/XMLS_settings_page.php',
	__("XML-Sitemap", EMU2_I18N_DOMAIN),
	__("Sitemap", EMU2_I18N_DOMAIN),
	0,
	XMLS_PLUGIN_DIRECTORY.'/XMLS_settings_page.php'
	);
	


	// or create options menu page
	//add_options_page(__('XML-Sitemap', EMU2_I18N_DOMAIN), __("XML-Sitemap", EMU2_I18N_DOMAIN), 9,  XMLS_PLUGIN_DIRECTORY.'/XMLS_settings_page.php');

	// or create sub menu page
	//$parent_slug="index.php";	# For Dashboard
	#$parent_slug="edit.php";		# For Posts
	// more examples at http://codex.wordpress.org/Administration_Menus
	//sadd_submenu_page( $parent_slug, __("XML-Sitemap", EMU2_I18N_DOMAIN), __("XML-Sitemap", EMU2_I18N_DOMAIN), 9, XMLS_PLUGIN_DIRECTORY.'/XMLS_settings_page.php');
}


function XMLS_register_settings() {
	//register settings
	register_setting( 'XMLS-settings-group', 'xmls_active' );
	
}

// check if debug is activated
function XMLS_debug() {
	# only run debug on localhost
	if ($_SERVER["HTTP_HOST"]=="localhost" && defined('EPS_DEBUG') && EPS_DEBUG==true) return true;
}
?>
