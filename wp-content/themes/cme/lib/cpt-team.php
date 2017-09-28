<?php

function enp_team_cpt() {
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
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'query_var'			=> false,
		'has_archive'		=> false,
		'menu_position' => 5,
		'menu_icon'			=> 'dashicons-groups',
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		//'taxonomies' 		=> array('category'),
		'has_archive'   => false,
	);
	register_post_type( 'team', $args );
	add_post_type_support( 'team', array( 'editor', 'page-attributes' ) );

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

	//register_taxonomy_for_object_type('category', 'team');
}
add_action( 'init', 'enp_team_cpt' );


/**
 * Filters author output for research template
 */
function enp_team_byline($name) {
  if( is_singular('research') ){
    $team = get_post_team_members();
    foreach($team as $member){
      $byline[] = sprintf( "<a href='" . esc_url( get_permalink($member->ID) ) . "' class=\"author\" rel=\"author\">%s</a>", $member->post_title);
    }
    return implode(', ',$byline);
  }
  return $name;
}
add_filter('the_author', 'enp_team_byline');

function get_post_team_members($all = false) {
	global $post;

	if( is_singular('research') ){
	 	return get_posts( array('post_type'=> 'team', 'post_status' => 'publish', 'post__in' => get_field('project_team_member'), 'orderby' => 'post__in', 'order' => 'ASC', 'posts_per_page' => -1 ));
	}
	return get_posts( array('post_type'=> 'team', 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => -1 ));

}

add_action( 'init', __NAMESPACE__ . '\\remove_custom_post_comment', 10 );

function remove_custom_post_comment() {
    remove_post_type_support( 'team', 'comments' );
}

function enp_display_team ($atts) {

	$a = shortcode_atts( array(
        'category' => '',
    ), $atts );

	$args = array('post_type'=> 'team', 'post_status' => 'publish', 'team_category' => $a['category'], 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => -1 );

	$team = get_posts( $args );

	ob_start();
	include( locate_template( 'templates/content-team.php' ) );
	//get_template_part( 'templates/content', 'team' );

	$out = ob_get_clean();

	return $out;

}
add_shortcode('team', __NAMESPACE__ . '\\enp_display_team');

?>
