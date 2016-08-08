<?php
/*
Plugin Name: Custom Menu Class
Plugin URI: http://wordpress.org/plugins/custom-menu-class/
Description: Select predefined CSS classes to menu items
Version: 0.2.6.1
Author: Theodoros Fabisch
Author URI: http://deving.de
License: GPL2
Thanks to the Author of "If Menu" - (Basis for this plugin: http://wordpress.org/plugins/if-menu/)
*/

/*  Copyright 2012 More WordPress (email: theodoros.aiken@gmail.com)

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

class Custom_Menu_Class
{
	public static function init()
	{
		if (is_admin())
		{
			add_action('admin_init', 'Custom_Menu_Class::admin_init');
			add_action('wp_update_nav_menu_item', 'Custom_Menu_Class::wp_update_nav_menu_item', 10, 2);
			add_filter('wp_edit_nav_menu_walker', create_function('', 'return "Custom_Menu_Class_Walker_Nav_Menu_Edit";'));
		}
		else if (!is_admin())
		{
			add_filter('wp_get_nav_menu_items', 'Custom_Menu_Class::wp_get_nav_menu_items');
		}
	}

	public static function get_classes()
	{
		$classes = apply_filters('custom_menu_css_classes', array());

		return $classes;
	}

	public static function wp_get_nav_menu_items($items)
	{
		$classes = Custom_Menu_Class::get_classes();
		$hidden_items = array();

		foreach($items as $key => $item)
		{
			$class = get_post_meta($item -> ID, 'Custom_Menu_Class_class', false);

			if (count($class) > 0 && is_array($class[0]))
			{
				$class = get_post_meta($item -> ID, 'Custom_Menu_Class_class', true);
			}

			if (in_array($item -> menu_item_parent, $hidden_items))
			{
				unset( $items[$key] );
				$hidden_items[] = $item -> ID;
			}
			else if ($class)
			{
				foreach ($class as $selected_class)
				{
					$item -> classes[] = $selected_class;
				}
			}
		}

		return $items;
	}

	public static function admin_init()
	{
		global $pagenow;

		if ($pagenow == 'nav-menus.php')
		{
			//wp_enqueue_script( 'custom-menu-class-js', plugins_url( 'custom-menu-class.js' , __FILE__ ), array( 'jquery' ) );
		}
	}

	public static function edit_menu_item_settings($item)
	{
		$classes = Custom_Menu_Class::get_classes();

		$Custom_Menu_Class_class = get_post_meta($item -> ID, 'Custom_Menu_Class_class', false);

		if (count($Custom_Menu_Class_class) > 0 && is_array($Custom_Menu_Class_class[0]))
		{
			$Custom_Menu_Class_class = get_post_meta($item -> ID, 'Custom_Menu_Class_class', true);
		}

		ob_start();
		?>
		<p class="custom-menu-class-condition description description-wide" style="display: <?php echo $Custom_Menu_Class_enable ? 'block' : 'block' ?>">
			<label for="edit-menu-item-custom-menu-class-<?php echo $item -> ID; ?>">
				<?php _e('CSS-Classes (predefined)<br /><small>Hold down the control (ctrl) button to select multiple options</small>', 'custom-menu-class') ?><br />
				<select id="edit-menu-item-custom-menu-class-<?php echo $item -> ID; ?>" class="widefat" name="menu-item-custom-menu-class[<?php echo $item -> ID; ?>][]" multiple="multiple">
					<?php foreach($classes as $class): ?>
						<option value="<?php echo $class['class']; ?>" <?php if (is_array($Custom_Menu_Class_class)) { selected(true, in_array($class['class'], $Custom_Menu_Class_class), true); } ?>><?php echo $class['name']; ?></option>
					<?php endforeach ?>
				</select>
			</label>
		</p>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	public static function wp_update_nav_menu_item($menu_id, $menu_item_db_id)
	{
		update_post_meta($menu_item_db_id, 'Custom_Menu_Class_class', $_POST['menu-item-custom-menu-class'][$menu_item_db_id]);
	}
}

/* ------------------------------------------------
	Custom Walker for nav items - with "if menu" plugin support
------------------------------------------------ */
require_once(ABSPATH . 'wp-admin/includes/nav-menu.php');

if (class_exists('If_Menu_Walker_Nav_Menu_Edit'))
{
	class Custom_Menu_Class_Walker_Nav_Menu_Edit extends If_Menu_Walker_Nav_Menu_Edit
	{
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
		{
			$desc_snipp = '<div class="menu-item-actions description-wide submitbox">';
			parent::start_el($output, $item, $depth, $args, $id);

			$pos = strrpos($output, $desc_snipp);

			if ($pos !== false)
			{
				$output = substr_replace($output, Custom_Menu_Class::edit_menu_item_settings($item).$desc_snipp, $pos, strlen($desc_snipp));
			}
		}
	}
}
else
{
	class Custom_Menu_Class_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit
	{
		function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
		{
			$desc_snipp = '<div class="menu-item-actions description-wide submitbox">';
			parent::start_el($output, $item, $depth, $args, $id);

			$pos = strrpos($output, $desc_snipp);

			if ($pos !== false)
			{
				$output = substr_replace($output, Custom_Menu_Class::edit_menu_item_settings($item).$desc_snipp, $pos, strlen($desc_snipp));
			}
		}
	}
}

/* ------------------------------------------------
	Include default classes for menu items
------------------------------------------------ */
include 'classes.php';

/* ------------------------------------------------
	Register plugin custom post type
------------------------------------------------ */
function register_cpt_cmcplugin()
{
	$labels = array(
		'name' => __('Menu CSS Classes', 'custom-menu-class'),
		'singular_name' => __('Menu CSS Classes', 'custom-menu-class'),
		'add_new' => __('Add', 'custom-menu-class'),
		'add_new_item' => __('Add new CSS Class', 'custom-menu-class'),
		'edit_item' => __('Edit CSS Class', 'custom-menu-class'),
		'new_item' => __('New CSS Class', 'custom-menu-class'),
		'view_item' => __('Menu CSS Classes', 'custom-menu-class'),
		'all_items' => __('Menu CSS Classes', 'custom-menu-class'),
		'search_items' => __('Search CSS Class', 'custom-menu-class'),
		'not_found' => __('No CSS Class found', 'custom-menu-class'),
		'not_found_in_trash' => __('No CSS Class found', 'custom-menu-class'),
		'parent_item_colon' => __('Parent CSS Class:', 'custom-menu-class'),
		'menu_name' => __('Menu CSS Classes', 'custom-menu-class')
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,
		'description' => 'Menu CSS Classes',
		'supports' => array(
			'title'
		) ,
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => 'options-general.php',//cmcplugin-settings-page
		'menu_position' => 100,
		'menu_icon' =>'dashicons-portfolio',
		'show_in_nav_menus' => false,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'has_archive' => false,
		'query_var' => true,
		'can_export' => true,
		'rewrite' => true
	);

	register_post_type('cmc_classes', $args);
}

add_action('init', 'register_cpt_cmcplugin');

/* ------------------------------------------------
	Custom post type columns
------------------------------------------------ */
function cmc_classes_columns($columns)
{
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __('CSS Class')
	);

	return $columns;
}

add_filter('manage_cmc_classes_posts_columns', 'cmc_classes_columns') ;

/* ------------------------------------------------
	Custom post type title
------------------------------------------------ */
function change_default_title($title)
{
     $screen = get_current_screen();
 
     if  ($screen->post_type == 'cmc_classes')
     {
          return __('Enter CSS Class here');
     }
}
 
add_filter( 'enter_title_here', 'change_default_title' );

/* ------------------------------------------------
	Include settings page
------------------------------------------------ */
function cmcplugin_menu()
{
    add_menu_page('Custom Menu Class', 'Custom Menu Class', 8, 'cmcplugin-settings-page', '', '');
	add_submenu_page('edit.php?post_type=cmc_classes', 'Settings', 'Settings', 8, 'cmcplugin-settings', 'cmc_classes');
}

//add_action('admin_menu', 'cmcplugin_menu');

/* ------------------------------------------------
	Run the plugin
------------------------------------------------ */
add_action('init', 'Custom_Menu_Class::init', 99);