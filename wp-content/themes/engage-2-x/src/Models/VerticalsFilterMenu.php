<?php
/**
* VerticalsFilterMenu is used when you want to have a filter menu that includes content from ALL verticals, organized by vertical, such as the /research/ page.
*/
namespace Engage\Models;

class VerticalsFilterMenu extends FilterMenu
{
	
	public function __construct($options) {
		parent::__construct($options);
		$this->linkBase =  'postType';
		$this->structure = 'vertical';
	}
	
	/**
	* Runs through all the posts and gets the terms they're a part of.
	*
	* @param $taxonomies ARRAY Empty array gets all possible taxonomies. Pass only the taxonomies you want to limit it.
	* @return ARRAY
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
			$filters['terms'][$category->slug] = $this->buildTopVerticalFilterTerm($category);
			
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
		$category_terms = get_the_terms($postID, 'category');
		
		if (!empty($category_terms) && is_array($category_terms)) {
			$category = $category_terms[0];
		} else {
			$category = get_the_terms($postID, 'category');
		}

		foreach($this->taxonomies as $taxonomy) {
			if($taxonomy === 'category') {
				continue;
			} else if ($taxonomy === 'team_category') {
				$terms = get_the_terms($postID, $taxonomy);
				if(empty($terms)) {
					continue;
				}
				foreach($terms as $term) {
					// Check if $vertical is not null before accessing its slug property
					if(!isset($filters['terms'][$vertical->slug]['terms'][$term->slug]) && $term->slug !== 'uncategorized') {
						// WARNING: Attempt to read property "slug" on array TODO
						$filters['terms'][$vertical->slug]['terms'][$term->slug] = $this->buildFilterTerm($term, $vertical);
					}
				}
			}
		}
		return $filters;
	}
	
	public function buildTopVerticalFilterTerm($term) {
		$filterTerm = $this->buildFilterTerm($term);
		$filterTerm['terms'] = []; // add in empty array to hold terms
		return $filterTerm;
	}
	
}

delete_transient('research-filter-menu');
