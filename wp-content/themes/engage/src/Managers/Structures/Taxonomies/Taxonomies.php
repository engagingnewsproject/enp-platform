<?php
/*
 * Registers post types
 */
namespace Engage\Managers\Structures\Taxonomies;

class Taxonomies {
	protected $taxonomies = [];
	public function __construct($taxonomies) {
		$this->taxonomies = $taxonomies;
	}

	public function run() {
		foreach($this->taxonomies as $taxonomy) {
			$this->$taxonomy();
		}
	}

	public function Verticals() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Verticals', 'taxonomy general name' ),
			'singular_name'     => _x( 'Vertical', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Verticals' ),
			'all_items'         => __( 'All Verticals' ),
			'parent_item'       => __( 'Parent Vertical' ),
			'parent_item_colon' => __( 'Parent Vertical:' ),
			'edit_item'         => __( 'Edit Vertical' ),
			'update_item'       => __( 'Update Vertical' ),
			'add_new_item'      => __( 'Add New Vertical' ),
			'new_item_name'     => __( 'New Vertical Name' ),
			'menu_name'         => __( 'Verticals' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'has_archive'		=> true,
			'rewrite'           => [ 'slug' => 'verticals' ],
		);

		// register it to ALL post type
		$postTypes = [
			'post', 'page', 'research', 'funders', 'team', 'attachment'
		];
		register_taxonomy( 'verticals', $postTypes, $args );
	}


}