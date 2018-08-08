<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;

class FilterMenu
{
    public $title = '',
           $filters = [],
           $postTypes = [],
           $taxonomies = [],
           $disallowedTaxonomies = ['post_tags', 'research-tags'],
           $taxonomyStructure = 'vertical', // when you want things organized by vertical
           $posts = [],
           $rootURL; // only used for vertical type structure

    public function __construct($options, $query = false) {
        $defaults = [
            'taxonomies' => [],
            'taxonomyStructure'  => 'sections',
            'postTypes'  => [],
            'posts' => [],
            'rootURL' => home_url('/')
        ];

        $options = array_merge($defaults, $options);

        $this->posts = $options['posts'];
        $this->taxonomies = $options['taxonomies'];
        $this->postTypes = $options['postTypes'];
        $this->taxonomyStructure = $options['taxonomyStructure'];
        $this->rootURL = $options['rootURL'];
    }

    public function addDisallowedTaxonomy($taxName) {
        if(!in_array($taxName, $this->disallowedTaxonomies)) {
            $this->disallowedTaxonomies[] = $taxName;
        }
    }

    public function build() {

        /*if(empty($this->taxonomies) && get_queried_object()) {
            $queriedObject = get_queried_object();
            // set smart defaults based on the post_type/taxonomy archive we're on
            if(get_class($queriedObject) === 'WP_Post_Type') {
                $this->postTypes[] = $queriedObject->name;
                // we're on a post_type archive page
                $this->addPostTypeTaxonomies($queriedObject->name);
            }
            elseif(get_class($queriedObject) === 'WP_Term') {
                // get the post type this is registered for, then set the taxonomies off of that
                $tax = get_taxonomy($queriedObject->taxonomy);
                foreach($tax->object_type as $postType) {
                    $this->postTypes[] = $postType;
                    $this->addPostTypeTaxonomies($postType);
                }
            }
        } 
        else if(empty($this->postTypes) && !empty($this->taxonomies)) {
            foreach($this->postTypes as $postType) {
                $this->addPostTypeTaxonomies($postType);
            }
        }*/

        $this->filters = ( $this->taxonomyStructure === 'vertical' ? $this->setVerticalFilters() : $this->setFilters() );

        return $this->filters;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function addPostTypeTaxonomies($postType) {
        foreach(get_object_taxonomies($postType) as $taxonomy) {
            $this->addTaxonomy($taxonomy);
        }
        
    }

    public function addTaxonomy($taxonomy) {
        if(!in_array($taxonomy, $this->taxonomies) && !in_array($taxonomy, $this->disallowedTaxonomies)) {
            $this->taxonomies[] = $taxonomy;
        }
    }


    /**
     * Runs through all the posts and gets the terms they're a part of.
     *
     * @return ARRAY
     */
    public function setFilters() {
        $filters = [];

        foreach($this->posts as $post) {
            // get all the terms
            foreach($this->taxonomies as $taxonomy) {
                $filters = $this->buildFilter($filters, $post->ID, $taxonomy);
            }
        }
        return $filters;
    }

    /**
     * Gets terms for a post based on taxonomy and builds it into the filters 
     * if not already present
     *
     * @param $filters ARRAY of current filters
     * @param $postID MIXED INT/STRING
     * @param $taxonomy STRING 
     * @return ARRAY
     */
    public function buildFilter($filters, $postID, $taxonomy) {

        $terms = get_the_terms($postID, $taxonomy);

        if(empty($terms)) {
            return $filters;
        }

        // check if this taxonomy already exists in the filters
        if(!isset($filters[$taxonomy])) {
            $tax = get_taxonomy($taxonomy);

            $filters[$taxonomy] = [
                'title' => $tax->label,
                'slug'  => $tax->name,
                'link'  => get_site_url() . '/' . $tax->rewrite['slug'],
                'terms' => []
            ];
        }

        // set the terms 
        foreach($terms as $term) {
            if(!isset($filters[$taxonomy]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
                $filters[$taxonomy]['terms'][$term->slug] = [
                    'ID'    => $term->term_id,
                    'slug'  => $term->slug,
                    'title' => $term->name,
                    'description' => $term->description,
                    'link'  => get_term_link($term),
                    'count' => $term->count
                ];
            }
        }

        return $filters;
    }

     /**
     * Runs through all the posts and gets the terms they're a part of.
     *
     * @param $taxonomies ARRAY Empty array gets all possible taxonomies. Pass only the taxonomies you want to limit it.
     * @return ARRAY
     */
    public function setVerticalFilters() {
        $filters = ['categories' => 
                        [
                            'title' => 'Categories',
                            'slug'  => 'vertical-categories',
                            'link'  => false,
                            'terms' => []
                        ]
                    ];

        $verticals = get_terms([
            'taxonomy' => 'verticals',
            'hide_empty' => true,
        ]);


        // set top level terms
        foreach($verticals as $vertical) {
            // add in an empty terms array to each one
            $filters['categories']['terms'][$vertical->slug] = $this->buildTopVerticalFilterTerm($vertical);

        }

        // now loop posts to get all other categories and which vertical they should get assigned to
        foreach($this->posts as $post) {
            $filters = $this->buildVerticalFilter($filters, $post->ID);
        }
        return $filters;
    }

    /**
     * Gets terms for a post based on taxonomy and builds it into the filters 
     * if not already present
     *
     * @param $filters ARRAY of current filters
     * @param $postID MIXED INT/STRING
     * @param $taxonomy STRING 
     * @return ARRAY
     */
    public function buildVerticalFilter($filters, $postID) {

        // get which vertical taxonomy this goes to
        $vertical = get_the_terms($postID, 'verticals')[0]->slug;

        foreach($this->taxonomies as $taxonomy) {
            if($taxonomy === 'vertical') {
                continue;
            }

            $terms = get_the_terms($postID, $taxonomy);
            if(empty($terms)) {
                continue;
            }
            foreach($terms as $term) {
                // check if this taxonomy already exists in the filters
                if(!isset($filters['categories']['terms'][$vertical]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
                    $filters['categories']['terms'][$vertical]['terms'][$term->slug] = $this->buildFilterTerm($term);

                    // append vertical filter to end of link
                    $filters['categories']['terms'][$vertical]['terms'][$term->slug]['link'] .= '?vertical='.$vertical;
                        }
            }
        }

        return $filters;
    }

    // If a section only has one term,
    public function pruneFilters($filters) {
        return $filters;
    }


    public function buildFilterTerm($term) {
        return  [
                    'ID'    => $term->term_id,
                    'slug'  => $term->slug,
                    'title' => $term->name,
                    'description' => $term->description,
                    'link'  => get_term_link($term),
                    'count' => $term->count
                ];
    }

    public function buildTopVerticalFilterTerm($term) {
        $filterTerm = $this->buildFilterTerm($term);
        $filterTerm['terms'] = []; // add in empty array to hold terms
        $filterTerm['link'] = $this->rootURL .'?vertical='.$term->slug;
        return $filterTerm;
    }
    
}
