<?php

/**
 * Register Publications post type and taxonomies.
 *
 * This class handles the registration of the Publications custom post type
 * and its associated taxonomies (categories and tags).
 *
 * @package Engage\Managers\Structures\PostTypes
 */

namespace Engage\Managers\Structures\PostTypes;

class Publications
{
	/**
	 * Initialize the Publications post type by registering necessary WordPress hooks.
	 *
	 * @return void
	 */
	public function run()
	{
		add_action('init', [$this, 'register']);
		add_action('init', [$this, 'registerTaxonomies'], 0);
	}

	/**
	 * Register the Publications custom post type.
	 *
	 * Sets up labels, capabilities, and configuration for the Publications post type.
	 * Enables REST API support and configures permalink structure.
	 *
	 * @return void
	 */
	public function register()
	{
		$labels = array(
			'name' => _x('Publications', 'post type general name'),
			'singular_name' => _x('Publication', 'post type singular name'),
			'add_new' => _x('Add New', 'publication'),
			'add_new_item' => __('Add New Publication'),
			'edit_item' => __('Edit Publication'),
			'new_item' => __('New Publication'),
			'all_items' => __('All Publications'),
			'view_item' => __('View Publication'),
			'search_items' => __('Search Publications'),
			'not_found' => __('Publication not found'),
			'not_found_in_trash' => __('Publication not found in trash'),
			'parent_item_colon' => '',
			'menu_name' => 'Publications'
		);
		$args = array(
			'labels' => $labels,
			'description' => '',
			'public' => true,
			'publicly_queryable' => true,
			'menu_position' => 5,
			'menu_icon' => 'dashicons-book',
			'supports' => array('title', 'thumbnail'),
			'has_archive' => true,
			'exclude_from_search' => false,
			'show_in_rest' => true,
			'capability_type' => 'post',
			'rewrite' => array(
				'slug' => 'publications',
				'with_front' => false,
				'feeds' => true,
				'pages' => true,
				'ep_mask' => EP_PERMALINK
			),
			'query_var' => true,
			'can_export' => true,
			'delete_with_user' => false,
			'show_in_nav_menus' => true,
			'taxonomies' => array('publication-categories', 'publication-tags')
		);
		register_post_type('publication', $args);

		// Flush rewrite rules only when needed
		if (get_option('publications_rewrite_rules_flushed') != true) {
			flush_rewrite_rules(false);
			update_option('publications_rewrite_rules_flushed', true);
		}
	}

	/**
	 * Register all taxonomies associated with the Publications post type.
	 *
	 * @return void
	 */
	public function registerTaxonomies()
	{
		$this->publicationCategories();
		$this->publicationTags();
	}

	/**
	 * Register the Publications Categories taxonomy.
	 *
	 * Creates a hierarchical taxonomy (like categories) for organizing publications posts.
	 * Configures labels, permalink structure, and admin UI options.
	 *
	 * @return void
	 */
	public function publicationCategories()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name' => _x('Publication Categories', 'taxonomy general name'),
			'singular_name' => _x('Publication Category', 'taxonomy singular name'),
			'search_items' => __('Search Publication Categories'),
			'all_items' => __('All Publication Categories'),
			'parent_item' => __('Parent Publication Category'),
			'parent_item_colon' => __('Parent Publication Category:'),
			'edit_item' => __('Edit Publication Category'),
			'update_item' => __('Update Publication Category'),
			'add_new_item' => __('Add New Publication Category'),
			'new_item_name' => __('New Publication Category Name'),
			'menu_name' => __('Publication Categories'),
		);

		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'has_archive' => true,
			'rewrite' => array(
				'slug' => 'publications',
				'with_front' => false,
				'hierarchical' => true
			),
		);
		register_taxonomy('publication-categories', array('publication'), $args);
	}

	/**
	 * Register the Publications Tags taxonomy.
	 *
	 * Creates a non-hierarchical taxonomy (like tags) for organizing publications posts.
	 * Configures labels, permalink structure, and admin UI options.
	 *
	 * @return void
	 */
	public function publicationTags()
	{
		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name' => _x('Publication Tags', 'taxonomy general name'),
			'singular_name' => _x('Publication Tag', 'taxonomy singular name'),
			'search_items' => __('Search Publication Tags'),
			'popular_items' => __('Popular Tags'),
			'all_items' => __('All Tags'),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __('Edit Tag'),
			'update_item' => __('Update Tag'),
			'add_new_item' => __('Add New Tag'),
			'new_item_name' => __('New Tag Name'),
			'separate_items_with_commas' => __('Separate publication tags with commas'),
			'add_or_remove_items' => __('Add or remove publication tags'),
			'choose_from_most_used' => __('Choose from the most used publication tags'),
			'not_found' => __('No publication tags found.'),
			'menu_name' => __('Publication Tags'),
		);

		$args = array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => false,
			'show_admin_column' => false,
			'show_in_menu' => false,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array('slug' => 'publication-tags'),
		);

		register_taxonomy('publication-tags', 'publication', $args);
	}
}
