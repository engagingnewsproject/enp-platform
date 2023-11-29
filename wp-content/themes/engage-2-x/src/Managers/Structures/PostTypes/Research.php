<?php

namespace Engage\Managers\Structures\PostTypes;

class Research {

	public function run() {
		add_action( 'init', [$this, 'register']);
		add_action( 'init', [$this, 'registerTaxonomies'], 0 );

	}

	public function register() {
		$labels = array(
			'name'               => _x( 'Research', 'post type general name' ),
			'singular_name'      => _x( 'Research', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'research paper' ),
			'add_new_item'       => __( 'Add New Research Paper' ),
			'edit_item'          => __( 'Edit Research Paper' ),
			'new_item'           => __( 'New Research Paper' ),
			'all_items'          => __( 'All Research Papers' ),
			'view_item'          => __( 'View Paper' ),
			'search_items'       => __( 'Search Research Papers' ),
			'not_found'          => __( 'Paper not found' ),
			'not_found_in_trash' => __( 'Paper not found in trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Research',
			'rewrite' 			 => array('slug' => 'research'),
		);
		$args = array(
			'labels'              => $labels,
			'description'         => '',
			'public'              => true,
			'menu_position'       => 5,
			'menu_icon'		      => 'dashicons-media-document',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'         => true,
			'exclude_from_search' => false,
			'show_in_rest'        => true,
		);
		register_post_type( 'research', $args );
	}

	public function registerTaxonomies() {
		$this->researchCategories();
		$this->researchTags();
	}

	public function researchCategories() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Research', 'taxonomy general name' ),
			'singular_name'     => _x( 'Research Category', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Research Categories' ),
			'all_items'         => __( 'All Research Categories' ),
			'parent_item'       => __( 'Parent Research Category' ),
			'parent_item_colon' => __( 'Parent Research Category:' ),
			'edit_item'         => __( 'Edit Research Category' ),
			'update_item'       => __( 'Update Research Category' ),
			'add_new_item'      => __( 'Add New Research Category' ),
			'new_item_name'     => __( 'New Research Category Name' ),
			'menu_name'         => __( 'Research Category' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'has_archive'		=> true,
			'rewrite'           => array( 'slug' => 'research-cats' ),
		);
		register_taxonomy( 'research-categories', array( 'research' ), $args );
	}

	public function researchTags() {

		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'                       => _x( 'Tags', 'taxonomy general name' ),
			'singular_name'              => _x( 'Tag', 'taxonomy singular name' ),
			'search_items'               => __( 'Search Research Tags' ),
			'popular_items'              => __( 'Popular Tags' ),
			'all_items'                  => __( 'All Tags' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Tag' ),
			'update_item'                => __( 'Update Tag' ),
			'add_new_item'               => __( 'Add New Tag' ),
			'new_item_name'              => __( 'New Tag Name' ),
			'separate_items_with_commas' => __( 'Separate research tags with commas' ),
			'add_or_remove_items'        => __( 'Add or remove research tags' ),
			'choose_from_most_used'      => __( 'Choose from the most used research tags' ),
			'not_found'                  => __( 'No research tags found.' ),
			'menu_name'                  => __( 'Research Tags' ),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'research-tags' ),
		);

		register_taxonomy( 'research-tags', 'research', $args );
	}
}


