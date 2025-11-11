<?php

/**
 * ResearchFilterMenu is used when you want to have a filter menu that includes content from ALL research categories.
 */

namespace Engage\Models;

use function get_field;
use Engage\Models\URLConstructor;

class ResearchFilterMenu extends FilterMenu
{
	protected $options;
	public $urlConstructor;

	public function __construct($options, $urlConstructor = null)
	{
		parent::__construct($options);
		$this->options = $options;
		$this->linkBase = 'postType';
		// The structure property tells the system that this filter menu should be 
		// organized by research categories rather than by post types or verticals.
		$this->structure = 'research-categories';
		$this->urlConstructor = $urlConstructor ?: new URLConstructor();
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

		// Get the current term object if on a research category archive
		$current_term = null;
		if (is_tax('research-categories')) {
			$current_term = get_queried_object();
		}

		// Check if we're on Media Ethics or a child of Media Ethics
		$is_media_ethics = false;
		$media_ethics    = get_term_by('slug', 'media-ethics', 'research-categories');
		if ($media_ethics && $current_term) {
			if ($current_term->term_id == $media_ethics->term_id || $current_term->parent == $media_ethics->term_id) {
				$is_media_ethics = true;
			}
		}

		// Gather admin-configured category selections from Site Options.
		$selected_categories        = [];
		$media_ethics_selected_cats = [];

		$archive_settings = get_field('archive_settings', 'option');

		if (is_array($archive_settings)) {
			if (isset($archive_settings['research_post_type']['research_sidebar_filter']) && is_array($archive_settings['research_post_type']['research_sidebar_filter'])) {
				$selected_categories = array_map('intval', $archive_settings['research_post_type']['research_sidebar_filter']);
			}

			// Media Ethics sidebar order lives at the top level of Site Options, so read it separately.
			if (isset($archive_settings['media_ethics_category']['media_ethics_sidebar_order']) && is_array($archive_settings['media_ethics_category']['media_ethics_sidebar_order'])) {
				$media_ethics_selected_cats = array_map('intval', $archive_settings['media_ethics_category']['media_ethics_sidebar_order']);
			}
		}

		// The legacy field stores the IDs under a flat option name; keep this fallback for compatibility.
		if (empty($selected_categories)) {
			$fallback_selected = get_field('archive_settings_research_post_type_research_sidebar_filter', 'option');
			if (is_array($fallback_selected)) {
				$selected_categories = array_map('intval', $fallback_selected);
			}
		}

		// Fallbacks for Media Ethics ordering: first a flat option, then the group if it’s saved outside archive_settings.
		if (empty($media_ethics_selected_cats)) {
			$fallback_media_ethics = get_field('archive_settings_research_post_type_media_ethics_category_media_ethics_sidebar_order', 'option');
			if (is_array($fallback_media_ethics)) {
				$media_ethics_selected_cats = array_map('intval', $fallback_media_ethics);
			}

			if (empty($media_ethics_selected_cats)) {
				$direct_media_ethics_group = get_field('archive_settings_media_ethics_category', 'option');
				if (is_array($direct_media_ethics_group) && isset($direct_media_ethics_group['media_ethics_sidebar_order']) && is_array($direct_media_ethics_group['media_ethics_sidebar_order'])) {
					$media_ethics_selected_cats = array_map('intval', $direct_media_ethics_group['media_ethics_sidebar_order']);
				}
			}
		}

		if ($is_media_ethics) {
			// Build the Media Ethics sidebar from its child terms.
			$subcategories = get_terms([
				'taxonomy' => 'research-categories',
				'parent' => $media_ethics->term_id,
				'hide_empty' => true
			]);

			if (is_wp_error($subcategories)) {
				error_log('Error getting media ethics categories: ' . $subcategories->get_error_message());
				return $filters;
			}

			// List of IDs the admin picked, keeping the drag order intact.
			$order_source = !empty($media_ethics_selected_cats) ? $media_ethics_selected_cats : $selected_categories;
			$subcategory_ids = array_map(static function ($term) {
				return (int) $term->term_id;
			}, $subcategories);

			// Drop any IDs that are no longer Media Ethics children and rebuild the list in the saved sequence.
			$order_source = array_values(array_intersect($order_source, $subcategory_ids));
			$ordered_subcategories = [];
			if (!empty($order_source)) {
				$terms_by_id = [];
				foreach ($subcategories as $term) {
					$terms_by_id[$term->term_id] = $term;
				}

				foreach ($order_source as $term_id) {
					if (isset($terms_by_id[$term_id])) {
						$ordered_subcategories[] = $terms_by_id[$term_id];
						unset($terms_by_id[$term_id]);
					}
				}

				// Append any remaining children that weren’t explicitly ordered (e.g. new categories without a selection yet).
				$subcategories = array_merge($ordered_subcategories, array_values($terms_by_id));
			}

			// Final list strictly mirrors the admin-defined order when provided.
			$filtered_subcategories = !empty($order_source)
				? $ordered_subcategories
				: $subcategories;

			foreach ($filtered_subcategories as $term) {
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
		if (!empty($selected_categories)) {
			$terms = get_terms([
				'taxonomy'   => 'research-categories',
				'hide_empty' => true,
				'include'    => $selected_categories,
				'orderby'    => 'include',
			]);
		} else {
			$terms = get_terms([
				'taxonomy' => 'research-categories',
				'hide_empty' => true,
			]);
		}

		if (is_wp_error($terms)) {
			error_log('Error getting research categories: ' . $terms->get_error_message());
			return $filters;
		}

		foreach ($terms as $term) {
			// Only include terms that are selected in the ACF field
			if (empty($selected_categories) || in_array($term->term_id, $selected_categories, true)) {
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
