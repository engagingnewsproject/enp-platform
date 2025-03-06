<?php

namespace Engage\Managers\Structures\PostTypes;

class Blogs
{

	// Constructor
	public function __construct() {}

	// Method to run the necessary actions
	public function run()
	{
		// Add action to register the post type
		add_action('init', [$this, 'register']);
		// Add action to register taxonomies (categories)
		add_action('init', [$this, 'registerTaxonomies'], 0);
	}

	// Method to register the custom post type 'blogs'
	public function register()
	{
		$labels = array(
			'name'               => _x('Blogs', 'post type general name'),
			'singular_name'      => _x('Blog', 'post type singular name'),
			'add_new'            => _x('Add New', 'Blog'),
			'add_new_item'       => __('Add New Blog'),
			'edit_item'          => __('Edit Blog'),
			'new_item'           => __('New Blog'),
			'all_items'          => __('All Blogs'),
			'view_item'          => __('View Blog'),
			'search_items'       => __('Search Blogs'),
			'not_found'          => __('Blog not found'),
			'not_found_in_trash' => __('Blog not found in trash'),
			'parent_item_colon'  => '',
			'menu_name'          => 'Blogs',
			'rewrite' 			 => array('slug' => 'blogs'),
		);
		$args = array(
			'labels'              => $labels,
			'description'         => '',
			'public'              => true,
			'menu_position'       => 5,
			'menu_icon'		      => 'dashicons-welcome-write-blog',
			'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
			'has_archive'         => true,
			'exclude_from_search' => false,
			'show_in_rest'        => true
		);
		// Register the custom post type 'blogs'
		register_post_type('blogs', $args);
	}
	// Method to register taxonomies (categories) for the custom post type
	public function registerTaxonomies()
	{
		$this->blogCategory(); // Call the method to register the blogs category taxonomy
	}

	// Method to register the 'blogs-category' taxonomy
	public function blogCategory()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x('Blogs', 'taxonomy general name'),
			'singular_name'     => _x('Blog Category', 'taxonomy singular name'),
			'search_items'      => __('Search Blog Categories'),
			'all_items'         => __('All Blog Categories'),
			'parent_item'       => __('Parent Blog Category'),
			'parent_item_colon' => __('Parent Blog Category:'),
			'edit_item'         => __('Edit Blog Category'),
			'update_item'       => __('Update Blog Category'),
			'add_new_item'      => __('Add New Blog Category'),
			'new_item_name'     => __('New Blog Category Name'),
			'menu_name'         => __('Blog Category'),
		);
		// Arguments for registering the 'blogs-category' taxonomy
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'has_archive'		=> true,
			'rewrite'           => array('slug' => 'blogs-category'),
		);
		// Register the 'blogs-category' taxonomy for the 'blogs' post type
		register_taxonomy('blogs-category', array('blogs'), $args);
	}
}
