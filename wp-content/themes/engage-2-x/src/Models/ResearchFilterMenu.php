<?php
/**
* ResearchFilterMenu is used when you want to have a filter menu that includes content from ALL research categories.
*/
namespace Engage\Models;

class ResearchFilterMenu extends FilterMenu
{
	
	public function __construct($options) {
		parent::__construct($options);
		$this->linkBase =  'postType';
		$this->structure = 'research-categories';
	}
	
	/**
	* Sets up the filters based on research-categories taxonomy
	*
	* @return ARRAY
	*/
	public function setFilters() {
		$filters = $this->buildBaseFilter();

		// Get all research-categories terms
		$research_categories = get_terms([
			'taxonomy' => 'research-categories',
			'hide_empty' => true,
		]);
		
		// Debug
		error_log('RESEARCH FILTER MENU: Found ' . count($research_categories) . ' research-categories terms');
		
		if (empty($research_categories) || is_wp_error($research_categories)) {
			error_log('RESEARCH FILTER MENU: No terms found or error');
			return $filters;
		}
		
		// Add each research category directly to the filters
		foreach($research_categories as $term) {
			// Skip uncategorized
			if($term->slug === 'uncategorized') {
				error_log('RESEARCH FILTER MENU: Skipping uncategorized term');
				continue;
			}
			
			// Add the term directly to the filters
			$filters['terms'][$term->slug] = $this->buildFilterTerm($term);
			error_log('RESEARCH FILTER MENU: Added research category: ' . $term->name . ' (' . $term->slug . ')');
		}
		
		error_log('RESEARCH FILTER MENU: Final filters structure: ' . print_r($filters, true));
		
		return $filters;
	}
	
	/**
	* Build a filter term for a taxonomy term
	*
	* @param object $term The term object
	* @return array The filter term array
	*/
	public function buildFilterTerm($term, $vertical = false, $postType = false) {
		// Get the term link for research-categories
		$link = $this->Permalinks->getTermLink([
			'terms' => [$term],
			'postType' => 'research',
			'base'  => $this->linkBase
		]);
		
		// Convert /research/category/term/ to /research/term/
		$link = str_replace('/research/category/', '/research/', $link);
		
		return [
			'ID'    => $term->term_id,
			'slug'  => $term->slug,
			'title' => $term->name,
			'description' => $term->description,
			'link'  => $link,
			'count' => $term->count,
			'taxonomy' => $term->taxonomy,
			'terms' => [] // Empty array for potential child terms
		];
	}
}

// Clear the transient to force rebuilding the menu
delete_transient('research-filter-menu');
