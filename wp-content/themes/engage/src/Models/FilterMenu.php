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
           $Permalink,
           $manualLinks; // Permalink class for building our custom links

    public function __construct($options) {
        $defaults = [
            'title' => 'Categories',
            'slug' => 'categories-menu',
            'taxonomies' => [],
            'taxonomyStructure'  => 'postTypes',
            'postTypes'  => [],
            'posts' => [],
            'manualLinks' => []
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
        $this->manualLinks = $options['manualLinks'];
    }

    public function build() {
        $this->filters = $this->setFilters();
        $this->addManualLinks();
        $this->pruneEmptyFilters();
        return $this->filters;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function buildBaseFilter() {
        $base = [
                'title' => $this->title,
                'slug'  => $this->slug,
                'structure' => $this->structure,
                'link'  => false,
                'terms' => []
            ];
        // get current vertical, if any
        $vertical = $this->Permalinks->getQueriedVertical(); 
        // add all the taxonomies in the order that they were created
        foreach($this->postTypes as $postType) {
            $postType = get_post_type_object($postType);   
            // check if this taxonomy already exists in the filters
            if(!isset($base['terms'][$postType->name])) {

                $base['terms'][$postType->name] = [
                    'title' => $postType->labels->name,
                    'slug'  => $postType->name,
                    'link'  => $this->Permalinks->getTermLink([
                        'terms' => [
                            $vertical
                        ],
                        'postType' =>  $postType->name,
                        'base'  => $this->linkBase
                    ]),
                    'terms' => []
                ];
            }
        }
        return $base;
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
        // get current vertical, if any
        $vertical = $this->Permalinks->getQueriedVertical();  
        // get post type of the taxonomy
        $postType = $this->Permalinks->getPostTypeByTaxonomy($taxonomy);   
        

        if(empty($terms)) {
            return $filters;
        }

        // set the terms 
        foreach($terms as $term) {
            if(!isset($filters['terms'][$postType]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
                $filters['terms'][$postType]['terms'][$term->slug] = $this->buildFilterTerm($term, $vertical, $postType);
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

    public function addManualLinks() {
        /*
        * Manual Links should be in format like
        *   'manualLinks' => [
                'events-by-date' => [
                    'title' => 'Archive',
                    'slug' => 'archive-section',
                    'link' => '',
                    'terms' => [[

                        'slug' => 'past-events',
                        'title' => 'Past Events',
                        'link' => site_url().'/events/past'
                    ]]
                ]
            ];
        */
        if($this->manualLinks) {
            foreach($this->manualLinks as $key => $val) {
                $this->filters['terms'][$key] = $val;
            }
             
        }
    }

    // if a term has an empty['terms'] array, prune it.
    public function pruneEmptyFilters() {
        foreach($this->filters['terms'] as $key => $val) {
            if(isset($val['terms']) && empty($val['terms'])) {
                unset($this->filters['terms'][$key]);
            }
        }
    }

}
