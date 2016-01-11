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
		'public'        => false,
		'menu_position' => 5,
		'menu_icon'		=> 'dashicons-groups',
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'has_archive'   => false,
	);
	register_post_type( 'team', $args );

}
add_action( 'init', 'enp_team_cpt' );


/**
 * Filters author output for research template
 */
function byline($name) {
  if( is_singular('research') ){
    $team = get_posts( array('post_type'=> 'team', 'post__in' => get_field('project_team_member') ));
    //var_dump($team);
    foreach($team as $member){
      $byline[] = sprintf( "<a href='" . esc_url( get_permalink($member->ID) ) . "' class=\"author\" rel=\"author\">%s</a>", $member->post_title);
    }
    return implode(', ',$byline);
  }
  return $name;
}
add_filter('the_author', __NAMESPACE__ . '\\byline');

add_action( 'init', __NAMESPACE__ . '\\remove_custom_post_comment', 10 );

function remove_custom_post_comment() {
    remove_post_type_support( 'team', 'comments' );
}

?>
