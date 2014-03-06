<?php
/**
 * Advanced code editor uninstaller.
 */
if(!defined('WP_UNINSTALL_PLUGIN')) 
	exit;
//delete file meta table
global $wpdb;
$table_name = $wpdb->prefix . 'filemeta';
$wpdb->query("DROP TABLE IF EXISTS `$table_name`");
//remove data from options table
delete_option('ace_options');