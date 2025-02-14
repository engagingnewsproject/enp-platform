<?php
/**
 * Set data needed for tile layout page.
 */

namespace Engage\Models;

use Timber;

/**
 * Class FilterMenu
 *
 * Manages the creation and organization of filter menus for different post types and taxonomies.
 *
 * @package Engage\Models
 */
class FilterMenu
{
    /**
     * The title of the filter menu.
     *
     * @var string
     */
    public $title;
    /**
     * The slug identifier for the filter menu.
     *
     * @var string
     */
    public $slug;
    /**
     * Array of posts to be filtered.
     *
     * @var array
     */
    public $posts;
    /**
     * Array of taxonomies that the filter menu is applied to.
     *
     * @var array
     */
    public $taxonomies;
    /**
     * Array of post types that the filter menu is applied to.
     *
     * @var array
     */
    public $postTypes;
    /**
     * Array of terms for the filter.
     *
     * @var array
     */
    public $terms = [];
    /**
     * Base URL for links.
     *
     * @var string
     */
    public $linkBase;

    /**
     * Constructor to initialize the FilterMenu object with provided options.
     *
     * @param array $options Array of options to configure the filter menu.
     */
    public function __construct($options = [])
    {
        $defaults = [
            'title'      => 'Filter',
            'slug'       => 'filter-menu',
            'posts'      => [],
            'taxonomies' => [],
            'postTypes'  => [],
            'linkBase'   => 'postType'
        ];

        $options = array_merge($defaults, $options);

        $this->title = $options['title'];
        $this->slug = $options['slug'];
        $this->posts = $options['posts'];
        $this->taxonomies = $options['taxonomies'];
        $this->postTypes = $options['postTypes'];
        $this->linkBase = $options['linkBase'];
    }
    
    /**
     * Build the filter menu by setting filters, adding manual links, and pruning empty filters.
     *
     * @return array The completed filter menu.
     */
    public function build()
    {
        $base = $this->buildBaseFilter();
        
        foreach ($this->posts as $post) {
            foreach ($this->taxonomies as $taxonomy) {
                $base = $this->buildFilter($base, $post->ID, $taxonomy);
            }
        }

        $this->terms = $base['terms'];
        $this->pruneEmptyFilters();

        return [
            'title' => $this->title,
            'slug'  => $this->slug,
            'terms' => $this->terms
        ];
    }
    
    /**
     * Retrieve the filters currently applied to the filter menu.
     *
     * @return array Array of filters.
     */
    public function getFilters()
    {
        return $this->terms;
    }
    
    /**
     * Build the base filter structure for the menu.
     *
     * @return array The base structure of the filter menu.
     */
    public function buildBaseFilter()
    {
        $base = [
            'title' => $this->title,
            'slug'  => $this->slug,
            'terms' => []
        ];

        foreach($this->postTypes as $postType) {
            $postType = get_post_type_object($postType);
            if(!isset($base['terms'][$postType->name])) {
                $base['terms'][$postType->name] = [
                    'title' => $postType->labels->name,
                    'slug'  => $postType->name,
                    'link'  => $this->getPostTypeLink($postType->name),
                    'terms' => []
                ];
            }
        }
        return $base;
    }
    
    /**
     * Set the filters by going through all posts and getting the terms they belong to.
     *
     * @return array The filters applied to the filter menu.
     */
    public function setFilters()
    {
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
     * Build a filter term for a specific post based on its taxonomy.
     *
     * @param array $filters The current array of filters.
     * @param int|string $postID The ID of the post.
     * @param string $taxonomy The taxonomy to filter by.
     * @return array The updated filters with the new term added.
     */
    public function buildFilter($filters, $postID, $taxonomy)
    {
        $terms = get_the_terms($postID, $taxonomy);
        $postType = $this->getPostTypeByTaxonomy($taxonomy);
        
        if(empty($terms)) {
            return $filters;
        }
        
        foreach($terms as $term) {
            if(!isset($filters['terms'][$postType]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
                $filters['terms'][$postType]['terms'][$term->slug] = $this->buildFilterTerm($term, $postType);
            }
        }
        
        return $filters;
    }
    
    /**
     * Build a filter term array for a specific term.
     *
     * @param object $term The term object to build the filter for.
     * @param mixed $postType The post type associated with the term.
     * @return array The filter term array.
     */
    public function buildFilterTerm($term, $postType)
    {
        return [
            'ID'          => $term->term_id,
            'slug'        => $term->slug,
            'title'       => $term->name,
            'description' => $term->description,
            'link'        => $this->getTermLink($term, $postType),
            'count'       => $term->count,
            'taxonomy'    => $term->taxonomy
        ];
    }
        
    /**
     * Prune filters with empty terms from the filter menu.
     *
     * @return void
     */
    public function pruneEmptyFilters()
    {
        foreach($this->terms as $key => $val) {
            if(isset($val['terms']) && empty($val['terms'])) {
                unset($this->terms[$key]);
            }
        }
    }

    protected function getPostTypeLink($postType)
    {
        return get_post_type_archive_link($postType);
    }

    protected function getTermLink($term, $postType)
    {
        return get_term_link($term);
    }

    protected function getPostTypeByTaxonomy($taxonomy)
    {
        $post_types = get_post_types();
        foreach($post_types as $post_type) {
            $taxonomies = get_object_taxonomies($post_type);
            if(in_array($taxonomy, $taxonomies)) {
                return $post_type;
            }
        }
        return false;
    }
}
