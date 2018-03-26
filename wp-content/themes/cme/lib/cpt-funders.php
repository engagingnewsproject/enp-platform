<?php

function enp_funders_cpt() {
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
        'query_var'			=> false,
        'has_archive'		=> false,
        'menu_position' => 5,
        'menu_icon'			=> 'dashicons-groups',
        'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
        //'taxonomies' 		=> array('category'),
        'has_archive'   => false,
    );
    register_post_type( 'funders', $args );
    add_post_type_support( 'funders', array( 'editor', 'page-attributes' ) );

    $args = array(
        'hierarchical'          => true,
        'labels'                => array('name' => 'Funders Category'),
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'Funders-category' ),
    );

    register_taxonomy( 'funders_category', 'funders', $args );

    //register_taxonomy_for_object_type('category', 'team');
}
add_action( 'init', 'enp_funders_cpt' );


/**
 * Filters author output for research template
 */
function enp_funders_byline($name) {
    if( is_singular('research') ){
        $funders = get_post_funders();
        foreach($funders as $organization){
            $byline[] = sprintf( "<a href='#%s' class=\"author\" rel=\"author\">%s</a>", $organization->post_name, $organization->post_title);
        }
        return implode(', ',$byline);
    }
    return $name;
}
add_filter('the_author', 'enp_funders_byline');

function get_post_funders($all = false) {
    global $post;

    return get_posts( array('post_type'=> 'funders', 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => -1 ));

}

add_action( 'init', __NAMESPACE__ . '\\remove_custom_post_comment', 10 );
/*
function remove_custom_post_comment() {
    remove_post_type_support( 'funders', 'comments' );
}*/

function enp_display_funders ($atts) {

    $a = shortcode_atts( array(
        'category' => '',
    ), $atts );

    $args = array('post_type'=> 'funders', 'post_status' => 'publish', 'funders_category' => $a['category'], 'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => -1 );

    $funders = get_posts( $args );

    ob_start();
    include( locate_template( 'templates/content-funders.php' ) );
    //get_template_part( 'templates/content', 'team' );

    $out = ob_get_clean();

    return $out;

}
add_shortcode('funders', __NAMESPACE__ . '\\enp_display_funders');

?>
