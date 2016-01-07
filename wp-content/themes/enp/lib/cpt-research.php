<?php

add_action( 'init', 'enp_research_cpt' );


function enp_research_cpt() {
	$labels = array(
		'name'               => _x( 'Research', 'post type general name' ),
		'singular_name'      => _x( 'Research Paper', 'post type singular name' ),
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
		'labels'        => $labels,
		'description'   => 'Engaging News Project research papers',
		'public'        => true,
		'menu_position' => 5,
		'menu_icon'		=> 'dashicons-media-document',
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'has_archive'   => false,
		'exclude_from_search' => false
	);
	register_post_type( 'research', $args );
}

// hook into the init action and call create_book_taxonomies when it fires
add_action( 'init', 'enp_research_taxonomies', 0 );

// create two taxonomies (categories and tags) for the post type "research"
function enp_research_taxonomies() {
	// Add new taxonomy, make it hierarchical (like categories)
	$labels = array(
		'name'              => _x( 'Research Categories', 'taxonomy general name' ),
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
		'rewrite'           => array( 'slug' => 'research-cats' ),
	);

	register_taxonomy( 'research-categories', array( 'research' ), $args );

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

/*
 * Replace Taxonomy slug with Post Type slug in url
 * Version: 1.1
 * Runs only when permalinks are updated
 */
function taxonomy_slug_rewrite($wp_rewrite) {
    $rules = array();
    // get all custom taxonomies
    $taxonomies = get_taxonomies(array('_builtin' => false), 'objects');
    // get all custom post types
    $post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');

    foreach ($post_types as $post_type) {
        foreach ($taxonomies as $taxonomy) {

            // go through all post types which this taxonomy is assigned to
            foreach ($taxonomy->object_type as $object_type) {

                // check if taxonomy is registered for this custom type
                if ($object_type == $post_type->rewrite['slug']) {

                    // get category objects
                    $terms = get_categories(array('type' => $object_type, 'taxonomy' => $taxonomy->name, 'hide_empty' => 0));

                    // make rules
                    foreach ($terms as $term) {
                        $rules[$object_type . '/' . $term->slug . '/?$'] = 'index.php?' . $term->taxonomy . '=' . $term->slug;
                    }
                }
            }
        }
    }
    // merge with global rules
    $wp_rewrite->rules = $rules + $wp_rewrite->rules;

}
add_filter('generate_rewrite_rules', 'taxonomy_slug_rewrite');

// Filter term links to clean taxonomy slugs
add_filter('term_link', 'taxonomy_link_rewrite', 10, 3);
function taxonomy_link_rewrite( $url, $term, $taxonomy ) {

    // grab taxonomy slug
    $tax = get_taxonomy( $taxonomy );

    return str_replace( $tax->rewrite['slug'], $tax->object_type[0], $url );
}

function fix_blog_menu_css_class( $classes, $item ) {
    if ( is_tax( 'research-categories' ) || is_singular( 'research' ) || is_post_type_archive( 'research' ) ) {
        if ( $item->object_id == get_option('page_for_posts') ) {
            $key = array_search( 'current_page_parent', $classes );
            if ( false !== $key )
                unset( $classes[ $key ] );
        }
        // check item for research page template
        if( get_page_template_slug( $item->object_id ) == 'template-research.php' ){
        	$classes[] = 'current_page_parent current-menu-item';
        }
    }
    return $classes;
}
add_filter( 'nav_menu_css_class', __NAMESPACE__ . '\\fix_blog_menu_css_class', 10, 2 );

// Adds support for Research Category icons
function research_get_category_filter( $terms, $taxonomies, $args ) {
	foreach( $terms as $term ){
		if( is_object($term) && $term->taxonomy == "research-categories" ){
				$term->cat_icon = get_field('research-cat-icon', $term);
		}
	}
	return $terms;
}
add_filter( 'get_terms', __NAMESPACE__ . '\\research_get_category_filter', 10, 3 );

function custom_excerpt_length( $length ) {
	return 20;
}
add_filter( 'excerpt_length', __NAMESPACE__ . '\\custom_excerpt_length', 999 );
?>
