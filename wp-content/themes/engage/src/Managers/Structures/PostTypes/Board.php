<?php

namespace Engage\Managers\Structures\PostTypes;

class Board extends PostTypes {

	public function __construct() {

	}

	public function run() {
		add_action( 'init', [$this, 'register']);
		add_action( 'init', [$this, 'registerTaxonomies'], 0 );

	}

	public function register() {
		$labels = array(
			'name'               => _x( 'Board', 'post type general name' ),
			'singular_name'      => _x( 'Board Member', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add New Board Member' ),
			'edit_item'          => __( 'Edit Board Member' ),
			'new_item'           => __( 'New Board Member' ),
			'all_items'          => __( 'All Board Members' ),
			'view_item'          => __( 'View Board' ),
			'search_items'       => __( 'Search Board' ),
			'not_found'          => __( 'Board member not found' ),
			'not_found_in_trash' => __( 'Board member not found in trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Board'
		);
		$args = array(
			'labels'        => $labels,
			'description'   => 'Board members',
			'public'        => true,
			//'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'query_var'			=> true,
			'has_archive'		=> true,
			'menu_position' 	=> 5,
			'menu_icon'			=> 'dashicons-groups',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		);
		register_post_type( 'board', $args );
	}

	public function registerTaxonomies() {
		$args = array(
			'hierarchical'          => true,
			'labels'                => array('name' => 'Board Category'),
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'board-category' ),
		);

		register_taxonomy( 'board_category', 'board', $args );
	}

}
