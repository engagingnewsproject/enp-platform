<?php

/**
 * Collection of Queries that modify the main query or utilities for other queries
 * 
 * This class manages:
 * - Post query modifications for archives and taxonomies
 * - Event queries for The Events Calendar
 * - Research and blog post queries
 * - Pagination and post count settings
 * 
 * @package Engage\Managers
 */

namespace Engage\Managers;

use Timber;

class Queries
{

	public function __construct() {}

	/**
	 * Initialize query modifications
	 * Sets up filters and actions for query handling
	 */
	public function run()
	{
		add_action('pre_get_posts', [$this, 'unlimitedPosts']);
		add_action('pre_get_posts', [$this, 'pastEventsQuery']);

		// Remove the filter to reset the value of the virtual page if is setup.
		// add_filter( 'posts_results', [$this, 'posts_results']);
		add_filter('tribe_pre_get_view', [$this, 'removeEmptyTribeEvent']);
	}

	/**
	 * Remove pagination for taxonomy and post type archives
	 * Excludes blog post archives which retain pagination
	 * 
	 * @param WP_Query $query The main WordPress query
	 */
	public function unlimitedPosts($query)
	{

		// if it's the main query, NOT a post/blog archive, and is a taxonomy or post type archive, then dump everything
		if ($query->is_main_query() && $query->get('post_type') !== 'post' && (is_tax() || is_post_type_archive())) {
			// increase post count to -1
			$query->set('posts_per_page', '-1');
		}
	}

	/**
	 * Modify event queries for past and all events
	 * Handles The Events Calendar queries for different views
	 * 
	 * @param WP_Query $query The main WordPress query
	 */
	public function pastEventsQuery($query)
	{
		// if it's the main query, NOT a post/blog archive, and is a taxonomy or post type archive, then dump everything
		if ($query->is_main_query() && $query->get('post_type') === 'tribe_events' && (is_tax() || is_post_type_archive())) {
			// do we want past events?
			if ($query->get('query_name', false) === 'past_events') {

				// increase post count to -1
				$query->set('meta_key', '_EventEndDate');
				// have the date comparison be today, but anytime before today so we don't accidentally remove an event too soon.
				$query->set('meta_value', date("Y-m-d") . ' 00:00:00');
				$query->set('meta_compare', '<');
				$query->set('orderby', '_EventStartDate');
				$query->set('orderby', 'ASC');
				$query->set('eventDisplay', 'custom');
			} else if ($query->get('query_name', false) === 'all_events') {
				$query->set('eventDisplay', 'custom');
			}
		}
	}

	/**
	 * Remove empty event placeholder from Tribe Events query
	 * Fixes issue with Timber where virtual page isn't removed
	 * 
	 * @return void
	 */
	public function removeEmptyTribeEvent()
	{

		foreach (tribe_get_global_query_object()->posts as $key => $val) {
			if ($val->ID == 0) {
				unset(tribe_get_global_query_object()->posts[$key]);
			}
		}
	}

	/**
	 * Get posts by research category
	 * 
	 * Example:
	 * ```php
	 * $posts = $queries->getPostByCategory('research', 'media-ethics');
	 * ```
	 * 
	 * @param string $postType Post type ('research' or 'blogs')
	 * @param string $category Research category slug
	 * @param array $extraQuery Additional WP_Query parameters
	 * @return array Array of Timber\Post objects
	 */
	public function getPostByCategory($postType, $category, $extraQuery = [])
	{
		// Build query with post type and category
		$query = array_merge([
			'post_type'      => $postType,
			'posts_per_page' => 1
		], $this->getResearchCategoryQuery($category));

		// Add any extra query parameters
		$query = array_merge($query, $extraQuery);

		return Timber::get_posts($query);
	}

	/**
	 * Build taxonomy query for research categories
	 * 
	 * @param string $category Research category slug
	 * @return array WP_Query tax_query parameters
	 */
	public function getResearchCategoryQuery($category)
	{
		return ['tax_query' => [
			[
				'taxonomy' => 'research-categories',
				'field'    => 'slug',
				'terms'    => $category
			]
		]];
	}

	/**
	 * Get all research categories
	 * 
	 * @return array Array of Timber\Term objects
	 */
	public function getResearchCategories()
	{
		return \Timber::get_terms([
			'taxonomy' => 'research-categories',
			'hide_empty' => true,
		]);
	}

	/**
	 * Get recent posts with optional filtering
	 * 
	 * Example:
	 * ```php
	 * $options = [
	 *     'postType' => 'research',
	 *     'postsPerPage' => 8,
	 *     'post__not_in' => [1, 2, 3]
	 * ];
	 * $posts = $queries->getRecentPosts($options);
	 * ```
	 * 
	 * @param array $options Query options
	 * @return array Array of Timber\Post objects
	 */
	public function getRecentPosts($options = [])
	{
		$defaults = [
			'postType'      => 'any',
			'postsPerPage'  => 10,
			'class'         => 'Engage\Models\Article',
			'extraQuery'    => [],
			'post__not_in'  => []
		];

		$options = array_merge($defaults, $options);
		$query = array_merge([
			'post_type'      => $options['postType'],
			'posts_per_page' => $options['postsPerPage'],
			'post__not_in'   => $options['post__not_in']
		], $options['extraQuery']);

		return Timber::get_posts($query);
	}

	/**
	 * Get upcoming events
	 * 
	 * @param array $options Query options
	 * @return array Array of Timber\Post objects
	 */
	public function getUpcomingEvents($options = [])
	{
		$defaults = [
			'postType'      => 'tribe_events',
			'postsPerPage'  => 10,
			'class'         => 'Engage\Models\Event',
			'extraQuery'    => []
		];
		$options = array_merge($defaults, $options);

		return $this->getRecentPosts($options);
	}
}
