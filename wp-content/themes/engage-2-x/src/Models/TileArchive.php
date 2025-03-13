<?php

/**
 * Set data needed for tile layout page
 */

namespace Engage\Models;

use Engage\Models\Event;

class TileArchive extends Archive
{
	public $filters = []; // when you want things organized by vertical

	public function __construct($options, $query = false)
	{

		$defaults = [
			'filters'    => []
		];

		$options = array_merge($defaults, $options);
		$this->filters = $options['filters'];

		parent::init($query);

		// loop through the posts and if it's an event, set it as the event model instead
		foreach ($this->posts as $key => $val) {
			if ($val->post_type === 'tribe_events') {
				// $this->posts[$key] = new Event($val->ID); // TODO: get events done.
			}
		}

		// This is usually already set from a global. If it's empty, then there's no sidebar
		if (!empty($this->filters)) {
			// get the current filter menu item
			$this->setCurrentFilter();
		}
	}

	/**
	 * Sets the 'current' and 'currentParent' flags in the filters array based on the current page context
	 * 
	 * This function determines which filter item should be marked as active in the filter menu.
	 * It handles three different structures:
	 * 1. research-categories: For research pages organized by categories
	 * 2. vertical: For pages organized by verticals (like Media Ethics)
	 * 3. postTypes: For pages organized by post types
	 * 
	 * The function works in two steps:
	 * 1. First determines the current slug based on either vertical, category, or post type
	 * 2. Then marks the appropriate filter items as current/active in the filters array
	 * 
	 * @return void
	 */
	public function setCurrentFilter()
	{
		// Initialize currentSlug as null - this will store the identifier for the current page
		$currentSlug = null;

		// Step 1: Determine the current slug based on the page structure
		// For research categories, verticals, or post type structures
		if (
			$this->filters['structure'] === 'research-categories' ||
			$this->filters['structure'] === 'postTypes'
		) {

			// If not vertical, check if we're on a category page
			if (isset($this->category) && $this->category && isset($this->category->slug)) {
				$currentSlug = $this->category->slug;
			}
		} else {
			// For other structures, use the post type name as the current slug
			if (isset($this->postType) && isset($this->postType->name)) {
				$currentSlug = $this->postType->name;
			}
		}

		// Step 2: Mark the appropriate filter items as current
		if ($this->filters['terms'] && $currentSlug) {
			// Loop through top-level terms (could be verticals or main categories)
			foreach ($this->filters['terms'] as $parentTerm) {
				// If we found the matching parent term
				if ($currentSlug === $parentTerm['slug']) {
					// Mark this term as the current parent
					$this->filters['terms'][$parentTerm['slug']]['currentParent'] = true;

					// Special handling for vertical taxonomies
					if ($this->category->taxonomy === 'verticals') {
						// If it's a vertical, mark it as current
						$this->filters['terms'][$parentTerm['slug']]['current'] = true;
					} else {
						// For non-verticals (like research categories), check child terms
						if (!empty($parentTerm['terms'])) {
							// Look for a matching child term (subcategory)
							foreach ($parentTerm['terms'] as $childTerm) {
								if ($childTerm['slug'] === $this->category->slug) {
									// Mark the child term as current when found
									$this->filters['terms'][$parentTerm['slug']]['terms'][$this->category->slug]['current'] = true;
									break;
								}
							}
						}
					}
					// Exit the loop once we've found and processed the matching term
					break;
				}
			}
		}
	}
}
