<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Add submenu page
add_action( 'admin_menu',                                       'remove_taxonomy_base_slug__add_submenu_page_action'                    );

// For registering styles and scripts for admin pages
add_action( 'admin_init',                                       'remove_taxonomy_base_slug__register_scripts_and_styles_admin_action'   );

// For printing registered scripts in the footer
add_action( 'admin_print_footer_scripts',                       'remove_taxonomy_base_slug__admin_print_footer_scripts_action', 1       );

// For printing registerred styles in the header
add_action( 'admin_print_styles',                               'remove_taxonomy_base_slug__admin_print_styles_action'                  );

// Add ajax function for saving all the settings from the admin panels
add_action( 'wp_ajax_remove_taxonomy_base_slug_admin_save',     'remove_taxonomy_base_slug__admin_save_action'                          );