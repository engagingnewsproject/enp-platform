<?php

namespace Engage\Managers\Structures\PostTypes;

class CaseStudy extends PostTypes {

	public function __construct() {

	}

	public function run() {
		add_action( 'init', [$this, 'register']);
		add_action( 'init', [$this, 'registerTaxonomies'], 0 );

	}

	public function register() {
		$labels = array(
			'name'               => _x( 'Case Studies', 'post type general name' ),
			'singular_name'      => _x( 'Case Study', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'case study' ),
			'add_new_item'       => __( 'Add New Case Study' ),
			'edit_item'          => __( 'Edit Case Study' ),
			'new_item'           => __( 'New Case Study' ),
			'all_items'          => __( 'All Case Studies' ),
			'view_item'          => __( 'View Case Study' ),
			'search_items'       => __( 'Search Case Studies' ),
			'not_found'          => __( 'Case Study not found' ),
			'not_found_in_trash' => __( 'Case Study not found in trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Case Studies',
			'rewrite' 			 => array('slug' => 'case-study'),
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
		register_post_type( 'case-study', $args );
	}

	public function registerTaxonomies() {
		$this->caseStudyCategory();
	}

	public function caseStudyCategory() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Case Studies', 'taxonomy general name' ),
			'singular_name'     => _x( 'Case Study Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Case Study Categories' ),
			'all_items'         => __( 'All Case Study Categories' ),
			'parent_item'       => __( 'Parent Case Study Category' ),
			'parent_item_colon' => __( 'Parent Case Study Category:' ),
			'edit_item'         => __( 'Edit Case Study Category' ),
			'update_item'       => __( 'Update Case Study Category' ),
			'add_new_item'      => __( 'Add New Case Study Category' ),
			'new_item_name'     => __( 'New Case Study Category Name' ),
			'menu_name'         => __( 'Case Study Category' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'has_archive'		=> true,
			'rewrite'           => array( 'slug' => 'case-study-category' ),
		);
		register_taxonomy( 'case-study-category', array( 'case-study' ), $args );
	}
}


