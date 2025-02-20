<?php
/*
 * URL Generation Model
 * 
 * This class handles the generation of URLs for the theme's custom taxonomy structure.
 * It maps taxonomy slugs to URL segments and builds URLs for the filter menu and other
 * navigation elements. For example, it generates URLs like /research/category/bridging-divides/
 * when given a research category term.
 *
 * @package Engage\Models
 */

namespace Engage\Models;

class Permalinks
{
	public $taxRewriteMap = [  // Map the slugs of the taxonomies to the corresponding name
		'category_name'             => 'category',
		'category'                  => 'category',
		'research-tags'             => 'tag',
		'research-categories'       => 'category',
		'team_category'             => 'category',
		'announcement-category'     => 'category',
		'blogs-category'            => 'category',
		'tribe_events_cat'          => 'category'
		// add new taxonomies with
		//'taxonomy-slug'          => 'category' or whatever you want the base name of the url to be
	];

	public $category;
	public $postType;

	public function __construct() {}

	public function getQueriedCategory()
	{
		foreach ($this->taxRewriteMap as $key => $val) {
			$category = get_query_var($key, false);
			if ($category) {
				// there's a weird mapping where the query_var doesn't match the slug for the WP default category
				if ($key === 'category_name') {
					$key = 'category';
				}
				return get_term_by('slug', $category, $key);
			}
		}
		return false;
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
	 * Builds URLs for taxonomy terms
	 *
	 * @param array $options Options for URL generation
	 * @return string The generated URL
	 */
	public function getTermLink($options = [])
	{
		$defaults = [
			'terms'     => [],
			'postType'  => false,
			'base'      => 'postType'  // Always use post type as base
		];

		$options = array_merge($defaults, $options);
		$terms = $options['terms'];
		$postType = ($options['postType'] ? $options['postType'] : get_query_var('post_type', false));
		$postType = ($postType === 'tribe_events' ? 'events' : $postType);

		// Start with the site URL
		$link = get_site_url();

		// Add the post type as the first URL segment
		$link .= ($postType ? '/' . $postType : '');

		// Add taxonomy terms to URL
		foreach ($terms as $term) {
			// Skip if term is not an object or is false/null
			if (!is_object($term) || empty($term)) {
				continue;
			}

			// Skip if term doesn't have required properties
			if (!isset($term->taxonomy) || !isset($term->slug)) {
				continue;
			}

			if (array_key_exists($term->taxonomy, $this->taxRewriteMap)) {
				$taxonomy = $this->taxRewriteMap[$term->taxonomy];
				$link .= '/' . $taxonomy . '/' . $term->slug;
			}
		}

		return $link;
	}
}
