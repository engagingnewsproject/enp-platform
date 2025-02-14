<?php
namespace Engage\Models;

/**
* CategoryFilterMenu is used when you want to have a filter menu that includes content 
* organized by categories, such as the /research/ page.
*/
class CategoryFilterMenu extends FilterMenu
{
    public function __construct($options) {
        parent::__construct($options);
        $this->linkBase = 'postType';
        $this->structure = 'category';
    }
    
    /**
    * Sets up the filters by building the base structure and adding terms
    *
    * @return array
    */
    public function setFilters() {
        $filters = $this->buildBaseFilter();
        
        $categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => true,
        ]);
        
        // set top level terms
        foreach($categories as $category) {
            // add in an empty terms array to each one
            $filters['terms'][$category->slug] = $this->buildTopCategoryFilterTerm($category);
        }
        
        // now loop posts to get all other categories
        foreach($this->posts as $post) {
            $filters = $this->buildCategoryFilter($filters, $post->ID);
        }
        
        return $filters;
    }
    
    /**
    * Builds filter terms for a specific post based on its taxonomies
    *
    * @param array $filters Current filter structure
    * @param int $postID Post ID to get terms for
    * @return array Updated filters
    */
    public function buildCategoryFilter($filters, $postID) {
        $category_terms = get_the_terms($postID, 'category');
        
        if (!empty($category_terms) && is_array($category_terms)) {
            $category = $category_terms[0];
            
            foreach($this->taxonomies as $taxonomy) {
                if($taxonomy === 'category') {
                    continue;
                }
                
                $terms = get_the_terms($postID, $taxonomy);
                if(empty($terms)) {
                    continue;
                }
                
                foreach($terms as $term) {
                    if(!isset($filters['terms'][$category->slug]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
                        $filters['terms'][$category->slug]['terms'][$term->slug] = $this->buildFilterTerm($term, $category);
                    }
                }
            }
        }
        
        return $filters;
    }
    
    /**
    * Builds the top-level category filter term
    *
    * @param object $term The term to build the filter for
    * @return array The built filter term
    */
    public function buildTopCategoryFilterTerm($term) {
        $filterTerm = $this->buildFilterTerm($term);
        $filterTerm['terms'] = []; // add in empty array to hold terms
        return $filterTerm;
    }
} 