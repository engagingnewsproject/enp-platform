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

		// Get the current term object if on a research category archive
		$current_term = null;
		if (is_tax('research-categories')) {
			$current_term = get_queried_object();
		}

		// Check if we're on Media Ethics or a child of Media Ethics
		$is_media_ethics = false;
		$media_ethics = get_term_by('slug', 'media-ethics', 'research-categories');
		if ($media_ethics && $current_term) {
			if ($current_term->term_id == $media_ethics->term_id || $current_term->parent == $media_ethics->term_id) {
				$is_media_ethics = true;
			}
		}

		if ($is_media_ethics) {
			// Get all children of Media Ethics
			$subcategories = get_terms([
				'taxonomy' => 'research-categories',
				'parent' => $media_ethics->term_id,
				'hide_empty' => true
			]);

			foreach ($subcategories as $term) {
				$thumbID = function_exists('get_field') ? get_field('category_featured_image', "research-categories_{$term->term_id}") : null;
				if ($thumbID) {
					$filters['terms'][$term->slug] = [
						'ID'    => $term->term_id,
						'slug'  => $term->slug,
						'title' => $term->name,
						'link'  => home_url('/research/category/media-ethics/' . $term->slug . '/'),
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
