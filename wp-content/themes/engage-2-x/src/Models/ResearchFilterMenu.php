<?php

/**
 * ResearchFilterMenu is used when you want to have a filter menu that includes content from ALL research categories.
 */

namespace Engage\Models;

use function get_field;

class ResearchFilterMenu extends FilterMenu
{
	protected $options;

	public function __construct($options)
	{
		parent::__construct($options);
		$this->options = $options;
		$this->linkBase = 'postType';
		// The structure property tells the system that this filter menu should be 
		// organized by research categories rather than by post types or verticals.
		$this->structure = 'research-categories';
	}

	/**
	 * Sets up the filters based on research-categories taxonomy
	 *
	 * @return array
	 */
	public function setFilters(): array
	{
		$filters = [
			'title' => $this->title,
			'slug'  => $this->slug,
			'structure' => $this->structure,
			'link'  => false,
			'terms' => []
		];

		// Debug logging
		// error_log('ResearchFilterMenu options: ' . print_r($this->options, true));

		// If we're on the media-ethics category page, get its subcategories
		if (isset($this->options['is_media_ethics']) && $this->options['is_media_ethics']) {
			// Get all research categories except uncategorized
			$args = [
				'taxonomy' => 'research-categories',
				'hide_empty' => true
			];
			$uncategorized = get_term_by('slug', 'uncategorized', 'research-categories');
			if ($uncategorized) {
				$args['exclude'] = $uncategorized->term_id;
			}
			$categories = get_terms($args);

			foreach ($categories as $term) {
				$thumbID = function_exists('get_field') ? get_field('category_featured_image', "research-categories_{$term->term_id}") : null;
				if ($thumbID) {
					$filters['terms'][$term->slug] = [
						'ID'    => $term->term_id,
						'slug'  => $term->slug,
						'title' => $term->name,
						'link'  => get_term_link($term),
						'taxonomy' => $term->taxonomy
					];
				}
			}
			return $filters;
		}

		// Default behavior for other cases
		$terms = get_terms([
			'taxonomy' => 'research-categories',
			'hide_empty' => true,
		]);

		if (is_wp_error($terms)) {
			error_log('Error getting research categories: ' . $terms->get_error_message());
			return $filters;
		}

		$selected_categories = get_field('archive_settings', 'option')['research_post_type']['research_sidebar_filter'] ?? [];
		
		foreach ($terms as $term) {
			// Only include terms that are selected in the ACF field
			if (in_array($term->term_id, $selected_categories)) {
				$filters['terms'][$term->slug] = $this->buildFilterTerm($term, false, 'research');
			}
		}

		return $filters;
	}

	/**
	 * Build a filter term for a taxonomy term
	 *
	 * @param object $term The term object
	 * @param mixed $unused Not used anymore, kept for backward compatibility.
	 * @param mixed $postType The post type associated with the term.
	 * @return array The filter term array
	 */
	public function buildFilterTerm($term, $unused = false, $postType = false)
	{
		return [
			'ID'    => $term->term_id,
			'slug'  => $term->slug,
			'title' => $term->name,
			'description' => $term->description,
			'link'  => $this->urlConstructor->getTermLink(
				[
					'terms' => [$term],
					'postType' => $postType,
					'base'  => $this->linkBase
				]
			),
			'count' => $term->count,
			'taxonomy' => $term->taxonomy
		];
	}
}
