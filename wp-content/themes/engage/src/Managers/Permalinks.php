<?php
/*
 * Modifications to permalinks
 */
namespace Engage\Managers;

class Permalinks {

    public function __construct() {

    }

    public function run() {
        add_filter('generate_rewrite_rules', [$this, 'taxonomySlugRewrite']);
    }

    /*
     * Replace Taxonomy slug with Post Type slug in url
     * Version: 1.1
     * Runs only when permalinks are updated
     */
    public function taxonomySlugRewrite($wp_rewrite) {

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
}