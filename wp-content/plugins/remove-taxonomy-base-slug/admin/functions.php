<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Registering scripts and sryles for later
function remove_taxonomy_base_slug__register_scripts_and_styles_admin_action() {
	$file        = __FILE__;
	$plugin_url  = plugin_dir_url ( $file );

	// Script file
	wp_register_script( 'remove_taxonomy_base_slug_admin_js', $plugin_url . 'js/admin.js' ); 
	
	// Style file
	wp_register_style( 'remove_taxonomy_base_slug_admin_css', $plugin_url . 'css/admin.css' ); 
}

// Function for printng script files in the footer
function remove_taxonomy_base_slug__admin_print_footer_scripts_action() {
	global $hook_suffix;

	if ( $hook_suffix == 'plugins_page_remove_taxonomy_base_slug' ) {
		wp_enqueue_script( 'remove_taxonomy_base_slug_admin_js' );
	}
}

// Function for printing styles in the header
function remove_taxonomy_base_slug__admin_print_styles_action() {
	global $hook_suffix;

	if ( $hook_suffix == 'plugins_page_remove_taxonomy_base_slug' ) {
		wp_enqueue_style( 'remove_taxonomy_base_slug_admin_css' );
	}
}

// Add submenu page
function remove_taxonomy_base_slug__add_submenu_page_action() {
	add_submenu_page( 'plugins.php', 'Remove Taxonomy Base Slug Settings', 'Remove Taxonomy Base Slug', 'administrator', 'remove_taxonomy_base_slug', 'remove_taxonomy_base_slug__main_function' );
}

// Function of what to display on submenu page
function remove_taxonomy_base_slug__main_function() { 
	global $title;
	
	// All options
	$options_array = remove_taxonomy_base_slug__settings_array(); ?>

	<div class="wrap">
		<div id="remove_taxonomy_base_slug-admin-panel">
			<form enctype="multipart/form-data" id="remove_taxonomy_base_slugform">
				<div id="remove_taxonomy_base_slug-admin-panel-header">
				
					<h3><?php echo $title; ?></h3>
					
				</div>
				<div id="remove_taxonomy_base_slug-admin-panel-main">
					<div id="remove_taxonomy_base_slug-admin-panel-menu">
					
						<?php echo remove_taxonomy_base_slug__machine_menu( $options_array ); ?>
						
					</div>
					<div id="remove_taxonomy_base_slug-admin-panel-content">
					
						<?php echo remove_taxonomy_base_slug__machine( $options_array ); ?>
						
					</div>
					<div class="clear"></div>
				</div>
				<div id="remove_taxonomy_base_slug-admin-panel-footer">
					<div id="remove_taxonomy_base_slug-admin-panel-footer-submit">
						<input type="submit" value="Apply Changes" class="button button-primary button-float-right" id="remove_taxonomy_base_slug__settings_array" />

						<div style="clear: both;"></div>
					</div>
					<div class="clear"></div>
				</div>
			</form>
			
			<?php wp_nonce_field( 'wp_ajax_remove_taxonomy_base_slug_ajax', 'remove_taxonomy_base_slug_nonce' ); ?>
			
		</div>
	</div>

<?php }

// Settings array
function remove_taxonomy_base_slug__settings_array() {

	$args = array(
	  'public'    => true,
	  'show_ui'   => true
	);

	$all_taxonomies_array = get_taxonomies( $args );

	$settings_options['remove_taxonomy_base_slug'] = array(
		'name'          => __( 'Quick Start', 'remove_taxonomy_base_slug' ),
		'options'       => array(
			'remove_taxonomy_base_slug_settings_what_taxonomies' => array(
				'name'          => __( 'Select taxonomies:', 'remove_taxonomy_base_slug' ),
				'desc'          => __( 'Select from the list which taxonomies base slug you would like to be removed.<br><strong>IMPORTANT:</strong> Update your permalinks after changes.', 'remove_taxonomy_base_slug'),
				'options'       => $all_taxonomies_array,
				'type'          => 'multiple',
				'default'       => array()
			)
		)
	);
	
	return $settings_options;
}

// Function for adding menu tab
function remove_taxonomy_base_slug__machine_menu( $options ) {

	$output = '<ul>';
	foreach( $options as $key => $arr ) {
		$output .= '<li class="remove_taxonomy_base_slug-admin-panel-menu-li">' . 
			'<a class="remove_taxonomy_base_slug-admin-panel-menu-link" href="#" id="remove_taxonomy_base_slug-admin-panel-menu-' . $key . '"><span></span>' . $arr['name'] . '</a>' . 
		'</li>' . "\n";
	}
	$output .= '</ul>';
	
	return $output;
}

// Function for generating inputs
function remove_taxonomy_base_slug__machine( $options ) {

	$output = '';

	foreach( $options as $key => $arr ) {
		if ( isset( $arr['options'] ) ) {

			$output .= '<div class="remove_taxonomy_base_slug-admin-panel-content-box" id="remove_taxonomy_base_slug-admin-panel-content-' . $key . '">';
		
				foreach ( $arr['options'] as $option_key => $arg ) {
					$val = get_option( $option_key, '' );

					$output .= '<div class="remove_taxonomy_base_slug-option">'; 
					$output .= '<h3 class="remove_taxonomy_base_slug-option-title">' . $arg['name'] . '</h3>' . "\n";
					
					if ( $arg['type'] == 'multiple' ) {

						$output .= '<select multiple="multiple" class="remove_taxonomy_base_slug-input-multiple" name="' . $option_key . '[]" id="' . $option_key . '">';
						foreach( $arg['options'] as $option => $key ) {
							$selected = '';
							$value2 = '';
							if ( is_array( $val ) ) {
								foreach( $val as $value2 ) {
									if ( $option == $value2 ) {
										$selected = ' selected="selected"'; 
									}
								}
							}
							$output .= '<option' . $selected . ' value="' . $option . '">' . $key . '</option>';
						}
						$output .= '</select>';
						
					}
					
					$output .= '<div class="clear"></div><small>' . $arg['desc'] . '</small>' . "\n";
					$output .= '</div>' . "\n";
				}
				
			$output .= '</div>';
		}
	}
	
	return $output;
}

// Function that is used with AJAX to save all settings from the admin panel
function remove_taxonomy_base_slug__admin_save_action() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		die( '0' );
	}
	check_ajax_referer( 'wp_ajax_remove_taxonomy_base_slug_ajax', 'remove_taxonomy_base_slug_nonce' );

	//flush_rewrite_rules();
	
	$function_name = $_POST['id'];

	// The data
	$data = $_POST['data'];

	// The options from the ID
	$options = $function_name();

	// Parses the string into variables
	parse_str( $data, $output );
	
	foreach ( $options as $o => $arr ) {
		if ( isset( $arr['options'] ) ) {
			foreach ( $arr['options'] as $options => $opt_arr ) {

				if ( isset( $output[$options] ) ) {
					$new_value = wp_unslash( $output[$options] );
					
					// If multiple
					if ( $opt_arr['type'] == 'multiple' ) {
						update_option( $options, $new_value );
					}
				} else {

					// If multiple
					if ( $opt_arr['type'] == 'multiple' ) {
						update_option( $options, array() );
					}					
				}

			}
		}
	}
	
	die( "1" );
}