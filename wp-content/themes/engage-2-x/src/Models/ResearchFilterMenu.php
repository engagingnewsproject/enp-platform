<?php

/**
 * ResearchFilterMenu is used when you want to have a filter menu that includes content from ALL research categories.
 */

namespace Engage\Models;

class ResearchFilterMenu extends FilterMenu
{

	public function __construct($options)
	{
		parent::__construct($options);
		$this->linkBase =  'postType';
		// The structure property tells the system that this filter menu should be 
		// organized by research categories rather than by post types or verticals.
		$this->structure = 'research-categories';
	}

	/**
	 * Sets up the filters based on research-categories taxonomy
	 *
	 * @return ARRAY
	 */
	public function setFilters()
	{
		$filters = $this->buildBaseFilter();

		// Get all research-categories terms
		$research_categories = get_terms([
			'taxonomy' => 'research-categories',
			'hide_empty' => true,
		]);

		if (empty($research_categories) || is_wp_error($research_categories)) {
			return $filters;
		}

		// Make sure the 'research' post type exists in the filters
		if (!isset($filters['terms']['research'])) {
			$filters['terms']['research'] = [
				'title' => 'Research',
				'slug' => 'research',
				'link' => home_url('/research/'),
				'terms' => []
			];
		}

		// Add each research category to the research post type's terms
		foreach ($research_categories as $term) {
			// Skip uncategorized
			if ($term->slug === 'uncategorized') {
				continue;
			}

			// Add the term to the research post type's terms
			$filters['terms']['research']['terms'][$term->slug] = $this->buildFilterTerm($term);
		}

		// error_log('RESEARCH FILTER MENU: Final filters structure: ' . print_r($filters, true));

		return $filters;
	}

	/**
	 * Build a filter term for a taxonomy term
	 *
	 * @param object $term The term object
	 * @return array The filter term array
	 */
	public function buildFilterTerm($term, $vertical = false, $postType = false)
	{
		// Get the term link for research-categories
		$link = $this->urlConstructor->getTermLink([
			'terms' => [$term],
			'postType' => 'research',
			'base'  => $this->linkBase
		]);
		error_log('RESEARCH FILTER MENU: Term link: ' . $link);
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
