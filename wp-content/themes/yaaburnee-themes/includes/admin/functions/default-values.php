<?php
	get_template_part(THEME_INCLUDES."/lib/class-tgm-plugin-activation");
	/* -------------------------------------------------------------------------*
	 * 						SET DEFAULT VALUES BY THEME INSTALL					*
	 * -------------------------------------------------------------------------*/
	global $pagenow;
	
	// with activate istall option
	if ( is_admin() && isset($_GET['activated'] ) && $pagenow == 'themes.php' ) {
		$theme_logo = THEME_IMAGE_URL."yaaburnee_logo.png";
		$banner = '	<a href="http://www.different-themes.com" target="_blank"><img src="'.THEME_IMAGE_URL.'ad-728x90.jpg" alt="" title="" /></a>';
		$favicon = THEME_IMAGE_URL."favicon.ico";

		
		update_option(THEME_NAME."_logo", $theme_logo);
		update_option(THEME_NAME.'_blog_style', "1");
		update_option(THEME_NAME.'_default_cat_color', "222222");
		update_option(THEME_NAME.'_search', "off");
		update_option(THEME_NAME.'_similar_posts', "custom");
		update_option(THEME_NAME.'_carousel_slider_count', "8");
		update_option(THEME_NAME.'_main_slider_count', "5");
		update_option(THEME_NAME.'_banner_top', "on");
		update_option(THEME_NAME.'_banner_code', $banner);
		update_option(THEME_NAME.'_google_font_1', 'Arial');
		update_option(THEME_NAME.'_google_font_2', 'Roboto Slab');
		update_option(THEME_NAME.'_google_font_3', 'Roboto Slab');
		update_option(THEME_NAME.'_google_font_4', 'Roboto Slab');
		update_option(THEME_NAME.'_google_font_5', 'Roboto Slab');
		update_option(THEME_NAME.'_google_font_6', 'Roboto Slab');
		update_option(THEME_NAME.'_google_font_7', 'Roboto Slab');
		update_option(THEME_NAME.'_font_size_1', '14');
		update_option(THEME_NAME.'_font_size_2', '14');
		update_option(THEME_NAME.'_font_size_3', '14');
		update_option(THEME_NAME.'_font_size_4', '14');
		update_option(THEME_NAME.'_font_size_5', '14');
		update_option(THEME_NAME.'_font_size_6', '14');
		update_option(THEME_NAME.'_page_width', '1');

		update_option(THEME_NAME.'_color_1', 'ff9900');
		update_option(THEME_NAME.'_color_2', 'ff9900');
		update_option(THEME_NAME.'_color_3', '222222');
		update_option(THEME_NAME.'_color_4', '222222');

		update_option(THEME_NAME.'_menu_style', 'light');
		update_option(THEME_NAME.'_page_layout', 'wide');
		update_option(THEME_NAME.'_heaader_layout', 'normal');
		update_option(THEME_NAME.'_body_bg_type', 'color');
		update_option(THEME_NAME.'_body_color', 'dddddd');
		update_option(THEME_NAME.'_main_slider_count', '5');
		update_option(THEME_NAME.'_main_auto', 'true');
		update_option(THEME_NAME.'_main_caption', 'true');
		update_option(THEME_NAME.'_main_mode', 'horizontal');
		update_option(THEME_NAME.'_main_pause', '2000');
		update_option(THEME_NAME.'_main_speed', '500');
		update_option(THEME_NAME.'_carousel_slider_count', '8');
		update_option(THEME_NAME.'_main_carousel_auto', 'false');
		update_option(THEME_NAME.'_main_carousel_pause', '2000');
		update_option(THEME_NAME.'_main_carousel_speed', '500');
		update_option(THEME_NAME.'_small_carousel_auto', 'false');
		update_option(THEME_NAME.'_small_carousel_pause', '2000');
		update_option(THEME_NAME.'_small_carousel_speed', '500');
		update_option(THEME_NAME.'_post_auto', 'false');
		update_option(THEME_NAME.'_post_mode', 'fade');
		update_option(THEME_NAME.'_post_controls', 'true');
		update_option(THEME_NAME.'_post_caption', 'true');
		update_option(THEME_NAME.'_post_pause', '2000');
		update_option(THEME_NAME.'_post_speed', '500');
		update_option(THEME_NAME.'_woocommerce_auto', 'false');
		update_option(THEME_NAME.'_woocommerce_mode', 'fade');
		update_option(THEME_NAME.'_woocommerce_controls', 'false');
		update_option(THEME_NAME.'_woocommerce_caption', 'true');
		update_option(THEME_NAME.'_woocommerce_pause', '2000');
		update_option(THEME_NAME.'_woocommerce_speed', '500');
		update_option(THEME_NAME.'_breaking_slider_count', '8');
		update_option(THEME_NAME.'_breaking_auto', 'true');
		update_option(THEME_NAME.'_breaking_mode', 'vertical');
		update_option(THEME_NAME.'_breaking_pause', '2000');
		update_option(THEME_NAME.'_breaking_speed', '500');
		update_option(THEME_NAME.'_widget_auto', 'false');
		update_option(THEME_NAME.'_widget_pause', '2000');
		update_option(THEME_NAME.'_widget_speed', '500');

		
		//update_option(THEME_NAME."_footer_logo", $theme_logo_f);
		update_option(THEME_NAME."_favicon", $favicon);
		update_option(THEME_NAME.'_sidebar_position', "custom");
		update_option(THEME_NAME.'_about_author', "custom");
		update_option(THEME_NAME."_rss_url", get_bloginfo("rss_url"));
		update_option(THEME_NAME.'_show_first_thumb', "on");
		update_option(THEME_NAME.'_show_single_thumb', "on");
	}
	
	

add_action( 'tgmpa_register', 'my_theme_register_required_plugins' );
/**
 * Register the required plugins for this theme.
 *
 * In this example, we register two plugins - one included with the TGMPA library
 * and one from the .org repo.
 *
 * The variable passed to tgmpa_register_plugins() should be an array of plugin
 * arrays.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
function my_theme_register_required_plugins() {

	/**
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(

		// This is an example of how to include a plugin pre-packaged with a theme
		array(
			'name'     				=> 'Woocommerce', // The plugin name
			'slug'     				=> 'woocommerce', // The plugin slug (typically the folder name)
			'source'   				=> get_template_directory(). '/includes/lib/plugins/woocommerce.zip', // The plugin source
			'required' 				=> true, // If false, the plugin is only 'recommended' instead of required
			'version' 				=> '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
			'force_activation' 		=> false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
			'force_deactivation' 	=> false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
			'external_url' 			=> '', // If set, overrides default API URL and points to an external URL
		),


	);


	/**
	 * Array of configuration settings. Amend each line as needed.
	 * If you want the default strings to be available under your own theme domain,
	 * leave the strings uncommented.
	 * Some of the strings are added into a sprintf, so see the comments at the
	 * end of each line for what each argument will be.
	 */
	$config = array(
		'domain'       		=> THEME_NAME,         	// Text domain - likely want to be the same as your theme.
		'default_path' 		=> '',                         	// Default absolute path to pre-packaged plugins
		'parent_menu_slug' 	=> 'themes.php', 				// Default parent menu slug
		'parent_url_slug' 	=> 'themes.php', 				// Default parent URL slug
		'menu'         		=> 'install-required-plugins', 	// Menu slug
		'has_notices'      	=> true,                       	// Show admin notices or not
		'is_automatic'    	=> false,					   	// Automatically activate plugins after installation or not
		'message' 			=> '',							// Message to output right before the plugins table
		'strings'      		=> array(
			'page_title'                       			=> __( 'Install Required Plugins', THEME_NAME ),
			'menu_title'                       			=> __( 'Install Plugins', THEME_NAME ),
			'installing'                       			=> __( 'Installing Plugin: %s', THEME_NAME ), // %1$s = plugin name
			'oops'                             			=> __( 'Something went wrong with the plugin API.', THEME_NAME ),
			'notice_can_install_required'     			=> _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.' ), // %1$s = plugin name(s)
			'notice_can_install_recommended'			=> _n_noop( 'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_install'  					=> _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.' ), // %1$s = plugin name(s)
			'notice_can_activate_required'    			=> _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
			'notice_can_activate_recommended'			=> _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_activate' 					=> _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.' ), // %1$s = plugin name(s)
			'notice_ask_to_update' 						=> _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_update' 						=> _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' ), // %1$s = plugin name(s)
			'install_link' 					  			=> _n_noop( 'Begin installing plugin', 'Begin installing plugins' ),
			'activate_link' 				  			=> _n_noop( 'Activate installed plugin', 'Activate installed plugins' ),
			'return'                           			=> __( 'Return to Required Plugins Installer', THEME_NAME ),
			'plugin_activated'                 			=> __( 'Plugin activated successfully.', THEME_NAME ),
			'complete' 									=> __( 'All plugins installed and activated successfully. %s', THEME_NAME ), // %1$s = dashboard link
			'nag_type'									=> 'updated' // Determines admin notice type - can only be 'updated' or 'error'
		)
	);

	tgmpa( $plugins, $config );

}

?>