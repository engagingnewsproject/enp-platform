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
	
		// Get selected term IDs from ACF
		$archive_settings = get_field('archive_settings', 'option');
		$selected_term_ids = $archive_settings['research_sidebar_filter'] ?? [];
	
		// Make sure the 'research' post type exists in the filters
		// if (!isset($filters['terms']['research'])) {
		// 	$filters['terms']['research'] = [
		// 		'title' => 'Research',
		// 		'slug' => 'research',
		// 		'link' => home_url('/research/'),
		// 		'terms' => []
		// 	];
		// }
	
		// Add each research category to the research post type's terms
		foreach ($research_categories as $term) {
			// error_log("Term: " . print_r($term-, true));
			// Skip uncategorized
			if ($term->slug === 'uncategorized' || $term->slug === 'research') {
				continue;
			}
	
			// Only add terms that are in the selected term IDs
			if (in_array($term->term_id, $selected_term_ids)) {
				$filters['terms']['research']['terms'][$term->slug] = $this->buildFilterTerm($term);
			}
		}
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
