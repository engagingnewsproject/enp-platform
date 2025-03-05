<?php
/*
 * Modifications to permalinks
 */

namespace Engage\Models;

class Permalinks
{

	public $taxRewriteMap = [  // Map the slugs of the taxonomies to the corresponding name. Most just get changed straight to category.
		'category_name'             => 'category',
		'category'                  => 'category',
		'research-tags'             => 'tag',
		'research-categories'       => 'category',
		'team_category'             => 'category',
		'announcement-category'     => 'category',
		'blogs-category'            => 'category',
		'tribe_events_cat'          => 'category',
	];
	public $vertical;
	public $category;
	public $postType;


	public function __construct() {}

	// public function getQueriedCategory()
	// {
	//     foreach($this->taxRewriteMap as $key => $val) {
	//         $category = get_query_var($key, false);
	//         if($category) {
	//             // there's a weird mapping where the query_var doesn't match the slug for the WP default category, so we have to change the name
	//             if($key === 'category_name') {
	//                 $key = 'category';
	//             }
	//             return get_term_by('slug', $category, $key);
	//         }
	//     }

	//     return false;
	// }

	public function getQueriedVertical()
	{
		$vertical = get_query_var('verticals', false);

		return ($vertical ? get_term_by('slug', $vertical, 'verticals') : false);
	}

	public function getQueriedCategory()
	{
		$category = get_query_var('category_name', false);
		return ($category ? get_term_by('slug', $category, 'category') : false);
	}

	public function getQueriedPostType()
	{
		$postType = get_query_var('post_type', false);

		return ($postType ? get_post_type_object($postType) : false);
	}

	public function getPostTypeByTaxonomy($taxonomySlug)
	{
		$post_types = get_post_types();
		foreach ($post_types as $post_type) {
			$taxonomies = get_object_taxonomies($post_type);
			if (in_array($taxonomySlug, $taxonomies)) {
				return $post_type;
			}
		}
	}

	/**
	 * Builds the correct link for all our crazy term structures
	 *
	 */
	public function getTermLink($options = [])
	{
		$defaults = [
			'terms'     => [],
			'postType'  => false,
			'base' => 'postType' // do we STAART with the vertical term or post type?
		];

		$options = array_merge($defaults, $options);

		// Convert 'vertical' base to 'category' for backward compatibility
		if ($options['base'] === 'vertical') {
			$options['base'] = 'category';

			// Also convert any 'verticals' terms to 'category' terms if needed
			foreach ($options['terms'] as &$term) {
				if (is_object($term) && $term->taxonomy === 'verticals') {
					// Find equivalent category term
					$category = get_term_by('slug', $term->slug, 'category');
					if ($category) {
						$term = $category;
					}
				}
			}
		}

		$terms = $options['terms'];
		$postType = ($options['postType'] ? $options['postType'] : get_query_var('post_type', false));

		// map tribe_events to event
		$postType = ($postType === 'tribe_events' ? 'events' : $postType);
		$base = $options['base'];
		$category = false;

		// set our category, if any
		foreach ($terms as $term) {
			if (!is_object($term)) {
				continue;
			}
			if ($term->taxonomy === 'research-categories') {
				$category = $term;
			}
		}


		$link = get_site_url();

		// What's our base?
		if ($base === 'postType') {
			// Start with the post type
			$link .= ($postType ? '/' . $postType : '');

			// Add any other taxonomies
			foreach ($terms as $term) {
				if (!is_object($term)) {
					continue;
				}
				// Skip the category since we already added it
				if ($term->taxonomy === 'category') {
					continue;
				}
				// For research-categories, add it with /category/ prefix
				if ($term->taxonomy === 'research-categories') {
					$link .= '/category/' . $term->slug;
					continue;
				}
				if (array_key_exists($term->taxonomy, $this->taxRewriteMap)) {
					$taxonomy = $this->taxRewriteMap[$term->taxonomy];
					// Add it to the link
					$link .= '/' . $taxonomy . '/' . $term->slug;
				}
			}
		} else {
			// For backward compatibility with the vertical-based URLs
			// Start with the vertical/category
			$link .= ($base === 'vertical' && $category ? '/category/' . $category->slug : '');

			// Add in the post type
			$link .= ($postType ? '/' . $postType : '');

			// Add in any other terms
			foreach ($terms as $term) {
				if (!is_object($term)) {
					continue;
				}
				if ($base === 'vertical' && $term->taxonomy === 'category') {
					// Skip it
					continue;
				}
				if (array_key_exists($term->taxonomy, $this->taxRewriteMap)) {
					$taxonomy = $this->taxRewriteMap[$term->taxonomy];
					// Add it to the link
					$link .= '/' . $taxonomy . '/' . $term->slug;
				}
			}
		}

		return $link;
	}

	// Add rewrite rules for the /research/[category] format
	public function getCategoryRewrites()
	{
		$rules = [];

		// For post types with categories
		$post_types = ['blogs', 'announcement', 'team', 'board']; // Remove 'research'

		foreach ($post_types as $post_type) {
			// /blogs/[category]
			$rules[$post_type . '/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&category_name=$matches[1]';

			// For subcategories or additional taxonomies
			if ($post_type === 'blogs') {
				// /blogs/[category]/category/[blogs-category]
				$rules[$post_type . '/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&category_name=$matches[1]&blogs-category=$matches[2]';
			}
			// Add similar rules for other post types as needed
		}

		// Special handling for research post type
		$rules['research/category/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=$matches[1]';
		$rules['research/category/([^/]+)/tag/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=$matches[1]&research-tags=$matches[2]';

		// Add rule for media-ethics subcategories
		$rules['research/category/media-ethics/([^/]+)/?$'] = 'index.php?post_type=research&category_name=media-ethics&research-categories=$matches[1]';

		return $rules;
	}
}
