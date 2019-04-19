<?php

namespace Engage\Managers\Structures\PostTypes;

class Announcement extends PostTypes {

	public function __construct() {

	}

	public function run() {
		add_action( 'init', [$this, 'register']);
		add_action( 'init', [$this, 'registerTaxonomies'], 0 );

	}

	public function register() {
		$labels = array(
			'name'               => _x( 'Announcements', 'post type general name' ),
			'singular_name'      => _x( 'Announcement', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'announcement paper' ),
			'add_new_item'       => __( 'Add New Announcements Paper' ),
			'edit_item'          => __( 'Edit Announcement' ),
			'new_item'           => __( 'New Announcement' ),
			'all_items'          => __( 'All Announcements' ),
			'view_item'          => __( 'View Paper' ),
			'search_items'       => __( 'Search Announcements' ),
			'not_found'          => __( 'Paper not found' ),
			'not_found_in_trash' => __( 'Paper not found in trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Announcements',
			'rewrite' 			 => array('slug' => 'announcement'),
		);
		$args = array(
			'labels'        => $labels,
			'description'   => '',
			'public'        => true,
			'menu_position' => 5,
			'menu_icon'		=> 'dashicons-media-document',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'   => true,
			'exclude_from_search' => false
		);
		register_post_type( 'announcement', $args );
	}

	public function registerTaxonomies() {
		$this->announcementCategory();
	}

	public function announcementCategory() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Announcements', 'taxonomy general name' ),
			'singular_name'     => _x( 'Announcement Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Announcement Categories' ),
			'all_items'         => __( 'All Announcement Categories' ),
			'parent_item'       => __( 'Parent Announcement Category' ),
			'parent_item_colon' => __( 'Parent Announcement Category:' ),
			'edit_item'         => __( 'Edit Announcement Category' ),
			'update_item'       => __( 'Update Announcement Category' ),
			'add_new_item'      => __( 'Add New Announcement Category' ),
			'new_item_name'     => __( 'New Announcement Category Name' ),
			'menu_name'         => __( 'Announcement Category' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'has_archive'		=> true,
			'rewrite'           => array( 'slug' => 'announcement-category' ),
		);
		register_taxonomy( 'announcement-category', array( 'announcement' ), $args );
	}
}


