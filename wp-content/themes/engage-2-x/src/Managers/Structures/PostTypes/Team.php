<?php
/**
 * Register Team post type and taxonomies.
 */

namespace Engage\Managers\Structures\PostTypes;

class Team {

	public function __construct() {

	}

	public function run() {
		add_action( 'init', [$this, 'register']);
		add_action( 'init', [$this, 'registerTeamCategories'], 0 );
		add_action( 'init', [$this, 'registerTeamDesignation'], 0 );
		add_action( 'init', [$this, 'registerTeamSemester'], 0 );
	}

	public function register() {
		$labels = array(
			'name'               => _x( 'Team', 'post type general name' ),
			'singular_name'      => _x( 'Team Member', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add New Team Member' ),
			'edit_item'          => __( 'Edit Team Member' ),
			'new_item'           => __( 'New Team Member' ),
			'all_items'          => __( 'All Team Members' ),
			'view_item'          => __( 'View Team' ),
			'search_items'       => __( 'Search Team' ),
			'not_found'          => __( 'Team member not found' ),
			'not_found_in_trash' => __( 'Team member not found in trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Team'
		);
		$args = array(
			'labels'        => $labels,
			'description'   => 'Team members',
			'public'        => true,
			//'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'query_var'         => true,
			'has_archive'       => true,
			'menu_position'     => 5,
			'menu_icon'         => 'dashicons-groups',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		);
		register_post_type( 'team', $args );
	}

	public function registerTeamCategories() {
		$args = array(
			'hierarchical'          => true,
			'labels'                => array('name' => 'Team Category'),
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'team-category' ),
		);

		register_taxonomy( 'team_category', 'team', $args );
	}

	public function registerTeamDesignation() {
		$args = array(
			'hierarchical'          => true,
			'labels'                => array('name' => 'Team Designation'),
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'team-designation' ),
		);
		register_taxonomy( 'team_designation', 'team', $args );
	}

	public function registerTeamSemester() {
		$args = array(
			'hierarchical'          => true,
			'labels'                => array('name' => 'Team Semester'),
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'team-semester' ),
		);
		register_taxonomy( 'team_semester', 'team', $args );
	}

}
