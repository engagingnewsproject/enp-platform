<?php
/*
 * Modifications to permalinks
 */
namespace Engage\Models;

class Permalinks {

    public  $taxRewriteMap = [
            'research-tags'         => 'tag',
            'research-categories'   => 'category',
            'team_category'         => 'category',
            'verticals'             => 'vertical'
        ],
        $vertical,
        $category,
        $postType;


    public function __construct() {

    }

    public function getQueriedCategory() {
        foreach($this->taxRewriteMap as $key => $val) {
            $category = get_query_var($key, false);
            
            if($category) {
                return get_term_by('slug', $category, $key);
            }
        }

        return false;
    }

    public function getQueriedVertical() {
        $vertical = get_query_var('verticals', false);

        return ($vertical ? get_term_by('slug', $vertical, 'verticals') : false);
    }

    public function getQueriedPostType() {
        $postType = get_query_var('post_type', false);

        return ($postType ? get_post_type_object($postType) : false);
    }

    public function getPostTypeByTaxonomy( $taxonomySlug ){
        $post_types = get_post_types();
        foreach( $post_types as $post_type ){
            $taxonomies = get_object_taxonomies( $post_type );
            if( in_array( $taxonomySlug, $taxonomies ) ){
                return $post_type;
            }
        }
    }

    /**
     * Builds the correct link for all our crazy term structures
     *
     */
    public function getTermLink($options = []) {
        $defaults = [
            'terms'     => [],
            'postType'  => false,
            'base' => 'postType' // do we STAART with the vertical term or post type?
        ];

        $options = array_merge($defaults, $options);

        $terms = $options['terms'];
        $postType = ( $options['postType'] ? $options['postType'] : get_query_var('post_type', false));
        $base = $options['base'];
        $vertical = false;

        // set our vertical, if any
        foreach ($terms as $term) {
            if(!is_object($term)) {
                continue;
            }
            if($term->taxonomy === 'verticals') {
                $vertical = $term;
            } 
        }


        $link = get_site_url();

        // what's our base?
        // start with the vertical 
        $link .= ($base === 'vertical' ? '/vertical/'.$vertical->slug : '');

        // add in the post type
        $link .= ($postType ? '/' .$postType : '');

        // add in any terms
        foreach($terms as $term) {
            if($base === 'vertical' && $term->taxonomy === 'verticals') {
                // skip it
                continue;
            }
            if(array_key_exists($term->taxonomy, $this->taxRewriteMap)) {
                $taxonomy = $this->taxRewriteMap[$term->taxonomy];
                // add it to the link
                $link .= '/'.$taxonomy.'/'.$term->slug;
            }
        }

        return $link;
    }

}