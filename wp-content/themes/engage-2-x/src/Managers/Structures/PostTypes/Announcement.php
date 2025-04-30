<?php

namespace Engage\Managers\Structures\PostTypes;

class Announcement
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

	// Method to register the custom post type 'announcement'
	public function register()
	{
		$labels = array(
			'name'               => _x('Announcements', 'post type general name'),
			'singular_name'      => _x('Announcement', 'post type singular name'),
			'add_new'            => _x('Add New', 'announcement paper'),
			'add_new_item'       => __('Add New Announcements Paper'),
			'edit_item'          => __('Edit Announcement'),
			'new_item'           => __('New Announcement'),
			'all_items'          => __('All Announcements'),
			'view_item'          => __('View Paper'),
			'search_items'       => __('Search Announcements'),
			'not_found'          => __('Paper not found'),
			'not_found_in_trash' => __('Paper not found in trash'),
			'parent_item_colon'  => '',
			'menu_name'          => 'Announcements',
			'rewrite'            => array('slug' => 'announcement'),
		);
		$args = array(
			'labels'        => $labels,
			'description'   => '',
			'public'        => true,
			'menu_position' => 5,
			'menu_icon'     => 'dashicons-megaphone',
			'supports'      => array('title', 'editor', 'thumbnail', 'excerpt'),
			'has_archive'   => true,
			'exclude_from_search' => false
		);
		// Register the custom post type 'announcement'
		register_post_type('announcement', $args);
	}

	// Method to register taxonomies (categories) for the custom post type
	public function registerTaxonomies()
	{
		$this->announcementCategory(); // Call the method to register the announcement category taxonomy
	}

	// Method to register the 'announcement-category' taxonomy
	public function announcementCategory()
	{
		// Labels for the 'announcement-category' taxonomy
		$labels = array(
			'name'              => _x('Announcements', 'taxonomy general name'),
			'singular_name'     => _x('Announcement Category', 'taxonomy singular name'),
			'search_items'      => __('Search Announcement Categories'),
			'all_items'         => __('All Announcement Categories'),
			'parent_item'       => __('Parent Announcement Category'),
			'parent_item_colon' => __('Parent Announcement Category:'),
			'edit_item'         => __('Edit Announcement Category'),
			'update_item'       => __('Update Announcement Category'),
			'add_new_item'      => __('Add New Announcement Category'),
			'new_item_name'     => __('New Announcement Category Name'),
			'menu_name'         => __('Announcement Category'),
		);

		// Arguments for registering the 'announcement-category' taxonomy
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => 'announcement-category',
			'has_archive'       => true,
			'rewrite'           => array(
				'slug'         => 'announcement/category',
				'with_front'   => false,
				'hierarchical' => true
			),
		);

		// Register the 'announcement-category' taxonomy for the 'announcement' post type
		register_taxonomy('announcement-category', array('announcement'), $args);
	}
}
