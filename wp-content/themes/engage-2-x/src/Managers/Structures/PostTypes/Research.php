<?php

/**
 * Register Research post type and taxonomies.
 *
 * This class handles the registration of the Research custom post type
 * and its associated taxonomies (categories and tags).
 *
 * @package Engage\Managers\Structures\PostTypes
 */

namespace Engage\Managers\Structures\PostTypes;

class Research
{
	/**
	 * Initialize the Research post type by registering necessary WordPress hooks.
	 *
	 * @return void
	 */
	public function run()
	{
		add_action('init', [$this, 'register']);
		add_action('init', [$this, 'registerTaxonomies'], 0);
	}

	/**
	 * Register the Research custom post type.
	 *
	 * Sets up labels, capabilities, and configuration for the Research post type.
	 * Enables REST API support and configures permalink structure.
	 *
	 * @return void
	 */
	public function register()
	{
		$labels = array(
			'name' => _x('Research', 'post type general name'),
			'singular_name' => _x('Research', 'post type singular name'),
			'add_new' => _x('Add New', 'research paper'),
			'add_new_item' => __('Add New Research Paper'),
			'edit_item' => __('Edit Research Paper'),
			'new_item' => __('New Research Paper'),
			'all_items' => __('All Research Papers'),
			'view_item' => __('View Paper'),
			'search_items' => __('Search Research Papers'),
			'not_found' => __('Paper not found'),
			'not_found_in_trash' => __('Paper not found in trash'),
			'parent_item_colon' => '',
			'menu_name' => 'Research',
			'rewrite' => array('slug' => 'research'),
		);
		$args = array(
			'labels' => $labels,
			'description' => '',
			'public' => true,
			'publicly_queryable' => true,
			'menu_position' => 5,
			'menu_icon' => 'dashicons-analytics',
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
			'has_archive' => true,
			'exclude_from_search' => false,
			'show_in_rest' => true,
			'capability_type' => 'post',
			'rewrite' => array(
				'slug' => 'research',
				'with_front' => false,
				'feeds' => true,
				'pages' => true,
				'ep_mask' => EP_PERMALINK
			),
			'query_var' => true,
			'can_export' => true,
			'delete_with_user' => false,
			'show_in_nav_menus' => true,
			'taxonomies' => array('research-categories', 'research-tags')
		);
		register_post_type('research', $args);
	}

	/**
	 * Register all taxonomies associated with the Research post type.
	 *
	 * @return void
	 */
	public function registerTaxonomies()
	{
		$this->researchCategories();
		$this->researchTags();
	}

	/**
	 * Register the Research Categories taxonomy.
	 *
	 * Creates a hierarchical taxonomy (like categories) for organizing research posts.
	 * Configures labels, permalink structure, and admin UI options.
	 *
	 * @return void
	 */
	public function researchCategories()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x('Research', 'taxonomy general name'),
			'singular_name' => _x('Research Category', 'taxonomy singular name'),
			'search_items' => __('Search Research Categories'),
			'all_items' => __('All Research Categories'),
			'parent_item' => __('Parent Research Category'),
			'parent_item_colon' => __('Parent Research Category:'),
			'edit_item' => __('Edit Research Category'),
			'update_item' => __('Update Research Category'),
			'add_new_item' => __('Add New Research Category'),
			'new_item_name' => __('New Research Category Name'),
			'menu_name' => __('Research Category'),
		);

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_rest' => true,
			'query_var' => true,
			'has_archive' => true,
			'rewrite' => array(
				'slug' => 'research',
				'with_front' => false,
				'hierarchical' => true
			),
		);
		register_taxonomy('research-categories', 'research', $args);
	}

	/**
	 * Register the Research Tags taxonomy.
	 *
	 * Creates a non-hierarchical taxonomy (like tags) for organizing research posts.
	 * Configures labels, permalink structure, and admin UI options.
	 *
	 * @return void
	 */
	public function researchTags()
	{

		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name' => _x('Tags', 'taxonomy general name'),
			'singular_name' => _x('Tag', 'taxonomy singular name'),
			'search_items' => __('Search Research Tags'),
			'popular_items' => __('Popular Tags'),
			'all_items' => __('All Tags'),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __('Edit Tag'),
			'update_item' => __('Update Tag'),
			'add_new_item' => __('Add New Tag'),
			'new_item_name' => __('New Tag Name'),
			'separate_items_with_commas' => __('Separate research tags with commas'),
			'add_or_remove_items' => __('Add or remove research tags'),
			'choose_from_most_used' => __('Choose from the most used research tags'),
			'not_found' => __('No research tags found.'),
			'menu_name' => __('Research Tags'),
		);

		$args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array('slug' => 'research-tags'),
		);

		register_taxonomy('research-tags', 'research', $args);
	}
}
