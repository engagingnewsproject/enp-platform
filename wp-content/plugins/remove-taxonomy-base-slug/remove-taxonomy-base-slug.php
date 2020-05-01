<?php
/*
 Plugin Name: Remove Taxonomy Base Slug
 Plugin URI: http://wordpress.org/plugins/remove-taxonomy-base-slug/
 Description: This plugin can remove specific taxonomy base slug from your permalinks (Go to Plugins -> Remove Taxonomy Base Slug).
 Version: 2.1
 Author: Alexandru Vornicescu
 Author URI: http://alexvorn.com
 */
 
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Global variable
$plugin_file       = __FILE__;
$plugin_dir_path   = plugin_dir_path( $plugin_file );

// Include actions.php file
require( trailingslashit( $plugin_dir_path ) . 'actions.php' );

// Quick admin check and load if needed
if ( is_admin() ) {

	// Include admin/actions.php file
	require( $plugin_dir_path . 'admin/actions.php' );

	// Include admin/functions.php file
	require( $plugin_dir_path . 'admin/functions.php' );
}

// Function on plugin 
function remove_taxonomy_base_slug__activation_action() {
	// Nothing, just because
}

// Function on plugin deactivation
function remove_taxonomy_base_slug__deactivation_action() {
	// Nothing, just because
}

// Main function
function remove_taxonomy_base_slug__main() {
	global $wp_rewrite;

	$args = array(
	  'public'   => true,
	  '_builtin' => false
	); 

	$post_types = get_post_types( $args );

	// If not empty
	if ( ! empty( $post_types ) ) {
		foreach ( $post_types as $post_type ) {
		
			// Change custom post type rules
			add_filter( $post_type . '_rewrite_rules', 'remove_taxonomy_base_slug__remove_post_type_filter' );
		}
	}
	
	$taxonomies = get_option( 'remove_taxonomy_base_slug_settings_what_taxonomies', array() );

	// If not empty
	if ( ! empty ( $taxonomies ) ) {
		$global_tax = '';
		foreach ( $taxonomies as $taxonomy ) {
			$global_tax = $taxonomy;
			
			$blog_prefix = '';
			if ( function_exists( 'is_multisite' ) && is_multisite() && ! is_subdomain_install() && is_main_site() ) {
				$blog_prefix = 'blog/';
			}
			
			// Permastructs
			$wp_rewrite->extra_permastructs[$taxonomy]['struct'] = $blog_prefix . '%' . $taxonomy . '%';
			
			// Add our custom category rewrite rules
			add_filter( $taxonomy . '_rewrite_rules', 'remove_taxonomy_base_slug__rewrite_taxonomy_filter' );
			
			// Taxonomy created
			add_action( 'created_' . $taxonomy,    'flush_rewrite_rules' );

			// Taxonomy edited
			add_action( 'edited_' . $taxonomy,     'flush_rewrite_rules' );

			// Taxonomy Deleted
			add_action( 'delete_' . $taxonomy,     'flush_rewrite_rules' );
		}
	}

	// If not empty
	if ( ! empty ( $taxonomies ) ) {
		if ( ! empty( $global_tax ) ) {
			add_filter( $taxonomy . '_rewrite_rules', 'remove_taxonomy_base_slug__insert_post_type_filter' );
		}
	}
	
	add_filter( 'rewrite_rules_array', 'remove_taxonomy_base_slug__remove_post_type_base_filter' );
}

// If the slug of the terms is the same as the slug of a post type then remove all rules of the post type
function remove_taxonomy_base_slug__remove_post_type_filter( $post_type_rewrite ) {
	$count = 0;
	$value_we_need = '';
	foreach( $post_type_rewrite as $one_value ) {
		$count = $count + 1;
		if ( $count == 6 ) {
			$value_we_need = $one_value;
		}
	}

	preg_match( '/\x{003F}(.*?)=/', $value_we_need, $post_type_arr );
	$post_type = $post_type_arr[1];

	$taxonomies = get_option( 'remove_taxonomy_base_slug_settings_what_taxonomies', array() );
	
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
		foreach ( $terms as $term ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object->rewrite['slug'] == $term->slug ) {
				$post_type_rewrite = array();
			}
		}
	}

	return $post_type_rewrite;
}

// Main function of the plugin to modify the rules
function remove_taxonomy_base_slug__rewrite_taxonomy_filter( $term_rewrite ) {

	foreach( $term_rewrite as $one_value ) {
		preg_match( '#index.php.(?!attachment)(.*?)=.matches#', $one_value, $taxonomy_arr );
		
		if ( ! empty( $taxonomy_arr[1] ) ) {
			$taxonomy_name = $taxonomy_arr[1];
		}
	}
	
	if ( ! empty( $taxonomy_name ) ) {
		$args = array(
			'public'    => true,
			'show_ui'   => true
		);

		$taxonomies = get_taxonomies( $args );

		foreach ( $taxonomies as $tax ) {
			$tax_obj = get_taxonomy( $tax );

			if ( $taxonomy_name == $tax_obj->query_var ) {
				$taxonomy = $tax;
			}
		}

		$term_rewrite = array();
		$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );

		$get_taxonomy = get_taxonomy( $taxonomy );

		$hierarchical = $get_taxonomy->rewrite['hierarchical'];
		
		$blog_prefix = '';
		if ( function_exists( 'is_multisite' ) && is_multisite() && ! is_subdomain_install() && is_main_site() ) {
			$blog_prefix = 'blog/';
		}
		
		foreach ( $terms as $term ) {
			$term_nicename = $term->slug;
			
			if ( $term->parent != 0 and $hierarchical ) {
				$term_nicename = get_term_parents2( $term->parent, $taxonomy, false, '/', true ) . $term_nicename;
			}

			$term_rewrite[$blog_prefix . '(' . $term_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$']        = 'index.php?' . $taxonomy_name . '=$matches[1]&feed=$matches[2]';
			$term_rewrite[$blog_prefix . '(' . $term_nicename . ')/page/?([0-9]{1,})/?$']                         = 'index.php?' . $taxonomy_name . '=$matches[1]&paged=$matches[2]';
			$term_rewrite[$blog_prefix . '(' . $term_nicename . ')/?$']                                           = 'index.php?' . $taxonomy_name . '=$matches[1]';
		}
	}
	
	return $term_rewrite;
}

// Function for inserting the post type rules, below the tax rules
function remove_taxonomy_base_slug__insert_post_type_filter( $rewrite_rules ) {
	$args = array(
	  'public'   => true,
	  '_builtin' => false
	); 
	
	$taxonomies = get_option( 'remove_taxonomy_base_slug_settings_what_taxonomies', array() );
	$post_types = get_post_types( $args );
	
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
		foreach ( $terms as $term ) {
			$term_slug = $term->slug;
			foreach ( $post_types as $post_type ) {
				$post_type_object = get_post_type_object( $post_type );
				$post_type_object_slug = $post_type_object->rewrite['slug'];
				if ( $post_type_object_slug == $term_slug ) {
					$rewrite_rules[$post_type_object_slug . '/[^/]+/attachment/([^/]+)/?$']                               = 'index.php?attachment=$matches[1]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/attachment/([^/]+)/trackback/?$']                     = 'index.php?attachment=$matches[1]&tb=1';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?attachment=$matches[1]&feed=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$']      = 'index.php?attachment=$matches[1]&feed=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$']      = 'index.php?attachment=$matches[1]&cpage=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/([^/]+)/trackback/?$']                                      = 'index.php?' . $post_type . '=$matches[1]&tb=1';
					$rewrite_rules[$post_type_object_slug . '/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$']                  = 'index.php?' . $post_type . '=$matches[1]&feed=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/([^/]+)/(feed|rdf|rss|rss2|atom)/?$']                       = 'index.php?' . $post_type . '=$matches[1]&feed=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/([^/]+)/page/?([0-9]{1,})/?$']                              = 'index.php?' . $post_type . '=$matches[1]&paged=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/([^/]+)/comment-page-([0-9]{1,})/?$']                       = 'index.php?' . $post_type . '=$matches[1]&cpage=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/([^/]+)(/[0-9]+)?/?$']                                      = 'index.php?' . $post_type . '=$matches[1]&page=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/([^/]+)/?$']                                          = 'index.php?attachment=$matches[1]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/([^/]+)/trackback/?$']                                = 'index.php?attachment=$matches[1]&tb=1';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$']            = 'index.php?attachment=$matches[1]&feed=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$']                 = 'index.php?attachment=$matches[1]&feed=$matches[2]';
					$rewrite_rules[$post_type_object_slug . '/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$']                 = 'index.php?attachment=$matches[1]&cpage=$matches[2]';
				}
			}
		}
	}

	return $rewrite_rules;
}

// Remove post type slugs if needed
function remove_taxonomy_base_slug__remove_post_type_base_filter( $rewrite_rules ) {

	$args = array(
	  'public'   => true,
	  '_builtin' => false
	); 
	
	$taxonomies = get_option( 'remove_taxonomy_base_slug_settings_what_taxonomies', array() );
	$post_types = get_post_types( $args );
	
	// If not empty
	if ( ! empty( $taxonomies ) ) {
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( $taxonomy, array( 'hide_empty' => false ) );
			foreach ( $terms as $term ) {
				$term_slug = $term->slug;
				
				// If not empty
				if ( ! empty( $post_types ) ) {
					foreach ( $post_types as $post_type ) {
						$post_type_object = get_post_type_object( $post_type );
						$post_type_object_slug = $post_type_object->rewrite['slug'];
						if ( $post_type_object_slug == $term_slug ) {
							unset( $rewrite_rules[$post_type_object_slug . '/?$'] );
							unset( $rewrite_rules[$post_type_object_slug . '/feed/(feed|rdf|rss|rss2|atom)/?$'] );
							unset( $rewrite_rules[$post_type_object_slug . '/(feed|rdf|rss|rss2|atom)/?$'] );
							unset( $rewrite_rules[$post_type_object_slug . '/page/([0-9]{1,})/?$'] );
						}
					}
				}
			}
		}
	}
	
	return $rewrite_rules;
}

// For debugging
function remove_taxonomy_base_slug__debugging_filter( $rewrite_rules ) {
	print_r( $rewrite_rules );
	return $rewrite_rules;
}

// Function that should be in WordPress core
function get_term_parents2( $id, $taxonomy, $link = false, $separator = '/', $nicename = false, $deprecated = array() ) { 
	$term = get_term( $id, $taxonomy ); 

	$chain = '';
	
    $parents = get_ancestors( $id, $taxonomy ); 
 	array_unshift( $parents, $id ); 
	
    foreach ( array_reverse( $parents ) as $term_id ) { 
		$term = get_term( $term_id, $taxonomy ); 

		$name = ( $nicename ) ? $term->slug : $term->name; 
		if ( $link ) {
			$chain .= '<a href="' . get_term_link( $term->slug, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $term->name ) ) . '">' . $name . '</a>' . $separator; 
		} else {
			$chain .= $name.$separator;
		}
	}
	
	return $chain;
}