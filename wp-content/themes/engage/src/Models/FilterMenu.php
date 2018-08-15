<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;

class FilterMenu
{
    public $title = '',
           $slug = '',
           $filters = [],
           $postTypes = [],
           $taxonomies = [],
           $structure = 'postTypes', // when you want things organized by vertical
           $posts = [],
           $linkBase,
           $Permalink; // Permalink class for building our custom links

    public function __construct($options) {
        $defaults = [
            'title' => 'Categories',
            'slug' => 'categories-menu',
            'taxonomies' => [],
            'taxonomyStructure'  => 'postTypes',
            'postTypes'  => [],
            'posts' => []
        ];

        $options = array_merge($defaults, $options);
        $this->title = $options['title'];
        $this->slug = $options['slug'];
        $this->posts = $options['posts'];
        $this->taxonomies = $options['taxonomies'];
        $this->postTypes = $options['postTypes'];
        $this->taxonomyStructure = $options['taxonomyStructure'];
        $this->Permalinks = new Permalinks();
        $this->linkBase =  'vertical';
        $this->structure = 'postTypes';
    }

    public function build() {
        $this->filters = $this->setFilters();

        return $this->filters;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function buildBaseFilter() {
        return [
                'title' => $this->title,
                'slug'  => $this->slug,
                'structure' => $this->structure,
                'link'  => false,
                'terms' => []
            ];
    }

    /**
     * Runs through all the posts and gets the terms they're a part of.
     *
     * @return ARRAY
     */
    public function setFilters() {
        $filters = $this->buildBaseFilter();

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
        $Permalinks = new Permalinks();
        // get current vertical, if any
        $vertical = $Permalinks->getQueriedVertical();  
        // get post type of the taxonomy
        $postType = $Permalinks->getPostTypeByTaxonomy($taxonomy);   
        $postType = get_post_type_object($postType);   

        if(empty($terms)) {
            return $filters;
        }


        // check if this taxonomy already exists in the filters
        if(!isset($filters['terms'][$postType->name])) {
            $tax = get_taxonomy($taxonomy);

            $filters['terms'][$postType->name] = [
                'title' => $postType->labels->name,
                'slug'  => $postType->name,
                'link'  => $Permalinks->getTermLink([
                    'terms' => [
                        $vertical
                    ],
                    'postType' =>  $postType->name,
                    'base'  => $this->linkBase
                ]),
                'terms' => []
            ];
        }

        // set the terms 
        foreach($terms as $term) {
            if(!isset($filters['terms'][$postType->name]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
                $filters['terms'][$postType->name]['terms'][$term->slug] = $this->buildFilterTerm($term, $vertical, $postType->name);
            }
        }

        return $filters;
    }

    public function buildFilterTerm($term, $vertical = false, $postType = false) {

        return  [
                    'ID'    => $term->term_id,
                    'slug'  => $term->slug,
                    'title' => $term->name,
                    'description' => $term->description,
                    'link'  => $this->Permalinks->getTermLink(
                        [
                            'terms' => [
                                $vertical,
                                $term
                            ],
                            'postType' => $postType,
                            'base'  => $this->linkBase
                        ]),
                    'count' => $term->count,
                    'taxonomy' => $term->taxonomy
                ];


    }

}
