<?php

/**
 * Register Press post type and taxonomies.
 *
 * This class handles the registration of the Press custom post type
 * and its associated taxonomies (categories and tags).
 *
 * @package Engage\Managers\Structures\PostTypes
 */

namespace Engage\Managers\Structures\PostTypes;

class Press
{
	/**
	 * Initialize the Press post type by registering necessary WordPress hooks.
	 *
	 * @return void
	 */
	public function run()
	{
		add_action('init', [$this, 'register']);
		add_action('init', [$this, 'registerTaxonomies'], 0);
	}

	/**
	 * Register the Press custom post type.
	 *
	 * Sets up labels, capabilities, and configuration for the Press post type.
	 * Enables REST API support and configures permalink structure.
	 *
	 * @return void
	 */
	public function register()
	{
		$labels = array(
			'name' => _x('Press', 'post type general name'),
			'singular_name' => _x('Press', 'post type singular name'),
			'add_new' => _x('Add New', 'press'),
			'add_new_item' => __('Add New Press Article'),
			'edit_item' => __('Edit Press Article'),
			'new_item' => __('New Press Article'),
			'all_items' => __('All Press Articles'),
			'view_item' => __('View Press Article'),
			'search_items' => __('Search Press'),
			'not_found' => __('Press Article not found'),
			'not_found_in_trash' => __('Press Article not found in trash'),
			'parent_item_colon' => '',
			'menu_name' => 'Press'
		);
		$args = array(
			'labels' => $labels,
			'description' => '',
			'public' => true,
			'publicly_queryable' => true,
			'menu_position' => 5,
			'menu_icon' => 'dashicons-microphone',
			'supports' => array('title', 'thumbnail'),
			'has_archive' => true,
			'exclude_from_search' => false,
			'show_in_rest' => true,
			'capability_type' => 'post',
			'rewrite' => array(
				'slug' => 'press',
				'with_front' => false,
				'feeds' => true,
				'pages' => true,
				'ep_mask' => EP_PERMALINK
			),
			'query_var' => true,
			'can_export' => true,
			'delete_with_user' => false,
			'show_in_nav_menus' => true,
			'taxonomies' => array('press-categories')
		);
		register_post_type('press', $args);

		// Flush rewrite rules only when needed
		if (get_option('press_rewrite_rules_flushed') != true) {
			flush_rewrite_rules(false);
			update_option('press_rewrite_rules_flushed', true);
		}
	}

	/**
	 * Register all taxonomies associated with the Press post type.
	 *
	 * @return void
	 */
	public function registerTaxonomies()
	{
		$this->pressCategories();
		// Uncomment this to enable tags
		// $this->pressTags();
	}

	/**
	 * Register the Press Categories taxonomy.
	 *
	 * Creates a hierarchical taxonomy (like categories) for organizing press posts.
	 * Configures labels, permalink structure, and admin UI options.
	 *
	 * @return void
	 */
	public function pressCategories()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x('Press Categories', 'taxonomy general name'),
			'singular_name' => _x('Press Category', 'taxonomy singular name'),
			'search_items' => __('Search Press Categories'),
			'all_items' => __('All Press Categories'),
			'parent_item' => __('Parent Press Category'),
			'parent_item_colon' => __('Parent Press Category:'),
			'edit_item' => __('Edit Press Category'),
			'update_item' => __('Update Press Category'),
			'add_new_item' => __('Add New Press Category'),
			'new_item_name' => __('New Press Category Name'),
			'menu_name' => __('Press Categories'),
		);

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'has_archive' => true,
			'rewrite' => array(
				'slug' => 'press/category',
				'with_front' => false,
				'hierarchical' => true
			),
		);
		register_taxonomy('press-categories', array('press'), $args);
	}

	/**
	 * Register the Press Tags taxonomy.
	 *
	 * Creates a non-hierarchical taxonomy (like tags) for organizing press posts.
	 * Configures labels, permalink structure, and admin UI options.
	 *
	 * @return void
	 */
	// Uncomment this to enable tags
	// public function pressTags()
	// {
	// 	// Add new taxonomy, NOT hierarchical (like tags)
	// 	$labels = array(
	// 		'name' => _x('Press Tags', 'taxonomy general name'),
	// 		'singular_name' => _x('Press Tag', 'taxonomy singular name'),
	// 		'search_items' => __('Search Press Tags'),
	// 		'popular_items' => __('Popular Tags'),
	// 		'all_items' => __('All Tags'),
	// 		'parent_item' => null,
	// 		'parent_item_colon' => null,
	// 		'edit_item' => __('Edit Tag'),
	// 		'update_item' => __('Update Tag'),
	// 		'add_new_item' => __('Add New Tag'),
	// 		'new_item_name' => __('New Tag Name'),
	// 		'separate_items_with_commas' => __('Separate press tags with commas'),
	// 		'add_or_remove_items' => __('Add or remove press tags'),
	// 		'choose_from_most_used' => __('Choose from the most used press tags'),
	// 		'not_found' => __('No press tags found.'),
	// 		'menu_name' => __('Press Tags'),
	// 	);

	// 	$args = array(
	// 		'hierarchical' => false,
	// 		'labels' => $labels,
	// 		'show_ui' => true,
	// 		'show_admin_column' => true,
	// 		'update_count_callback' => '_update_post_term_count',
	// 		'query_var' => true,
	// 		'rewrite' => array('slug' => 'press-tags'),
	// 	);

	// 	register_taxonomy('press-tags', 'press', $args);
	// }
}
