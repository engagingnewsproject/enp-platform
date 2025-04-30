<?php
/**
 * Register Funders post type and taxonomies.
 */

namespace Engage\Managers\Structures\PostTypes;

class Funders {

	public function __construct() {

	}

	public function run() {
		add_action( 'init', [$this, 'register']);
		add_action( 'init', [$this, 'registerTaxonomies'], 0 );
	}

	public function register() {
		$labels = array(
			'name'               => _x( 'Funders', 'post type general name' ),
			'singular_name'      => _x( 'Organization', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add New Organization' ),
			'edit_item'          => __( 'Edit Organization' ),
			'new_item'           => __( 'New Organization' ),
			'all_items'          => __( 'All Funders' ),
			'view_item'          => __( 'View Funders' ),
			'search_items'       => __( 'Search Funders' ),
			'not_found'          => __( 'Organization not found' ),
			'not_found_in_trash' => __( 'Organization not found in trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Funders'
		);
		$args = array(
			'labels'        => $labels,
			'description'   => 'Funding Organizations',
			'public'        => true,
			//'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'query_var'         => false,
			'has_archive'       => false,
			'menu_position' => 5,
			'menu_icon'         => 'dashicons-groups',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'show_in_menu'  => false, // This will hide it from the admin sidebar
		);
		register_post_type( 'funders', $args );
	}

	public function registerTaxonomies() {
		$args = array(
			'hierarchical'          => true,
			'labels'                => array('name' => 'Funders Category'),
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => false,
			'rewrite'               => array( 'slug' => 'Funders-category' ),
		);

		register_taxonomy( 'funders_category', 'funders', $args );
	}

	
}
