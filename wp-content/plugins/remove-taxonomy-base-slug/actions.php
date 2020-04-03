<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Plugin Activation
register_activation_hook( $plugin_file, 'remove_taxonomy_base_slug__activation_action' );

// Plugin Deactivation
register_deactivation_hook( $plugin_file, 'remove_taxonomy_base_slug__deactivation_action' );

// Main function
add_action( 'init', 'remove_taxonomy_base_slug__main', 11 );

// For debugging
//add_filter( 'rewrite_rules_array', 'remove_taxonomy_base_slug__debugging_filter' );