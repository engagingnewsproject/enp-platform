<?php
/*
* Collection of Queries that modify the main query or utilities for other queries
*/
namespace Engage\Managers;
use Timber;

class Queries {
	
	public function __construct() {
		
	}
	
	public function run() {
		add_action( 'pre_get_posts', [$this, 'unlimitedPosts'] );
		add_action( 'pre_get_posts', [$this, 'pastEventsQuery'] );
		
		// Remove the filter to reset the value of the virtual page if is setup.
		// add_filter( 'posts_results', [$this, 'posts_results']);
		add_filter( 'tribe_pre_get_view', [$this, 'removeEmptyTribeEvent']);
	}
	
	// there aren't enough of each section to bother with pagination. Figure that out if/when we get there
	public function unlimitedPosts($query) {
		
		// if it's the main query, NOT a post/blog archive, and is a taxonomy or post type archive, then dump everything
		if ( $query->is_main_query() && $query->get('post_type') !== 'post' && (is_tax() || is_post_type_archive())) {
			// increase post count to -1
			$query->set( 'posts_per_page', '-1' );
		}
	}
	
	public function pastEventsQuery($query) {
		// if it's the main query, NOT a post/blog archive, and is a taxonomy or post type archive, then dump everything
		if ( $query->is_main_query() && $query->get('post_type') === 'tribe_events' && (is_tax() || is_post_type_archive())) {
			// do we want past events?
			if($query->get('query_name', false) === 'past_events' ) {
				
				// increase post count to -1
				$query->set( 'meta_key', '_EventEndDate' );
				// have the date comparison be today, but anytime before today so we don't accidentally remove an event too soon.
				$query->set( 'meta_value', date("Y-m-d").' 00:00:00' );
				$query->set( 'meta_compare', '<');
				$query->set( 'orderby', '_EventStartDate' );
				$query->set( 'orderby', 'ASC' );
				$query->set( 'eventDisplay', 'custom');
			}
			else if($query->get('query_name', false) === 'all_events' ) {
				$query->set( 'eventDisplay', 'custom');
			}
			
		}
	}
	
	/**
	* Tribe uses a virtual page in the loop to return some extra info while the query is happening. In Timber, this doesn't get removed. Use this to remove it.
	*/
	public function removeEmptyTribeEvent() {
		
		foreach(tribe_get_global_query_object()->posts as $key => $val) {
			if($val->ID == 0) {
				unset(tribe_get_global_query_object()->posts[$key]);
			}
		}
	}
	
	/**
	* Get featured research from a specific research category
	* Example: Get a featured research post from "Media Ethics" category
	* @param $category STRING Research category slug (e.g., 'media-ethics')
	* @param $postType = 'research' or 'blogs'
	* @return Post|false Returns first featured post or false if none found
	*/
	public function getFeaturedResearchByCategory($category, $postType = 'research') {
		// First try to get posts that are marked as featured
		$posts = $this->getPostByCategory($postType, $category, $this->getFeaturedResearchMetaQuery());
		
		if(empty($posts)) {
			// If no featured posts, get any post from this category
			$posts = $this->getPostByCategory($postType, $category);
		}
		return (!empty($posts) ? $posts[0] : false);
	}
	
	/**
	* Get featured blog from a specific research category
	* Example: Get a featured blog post from "Media Ethics" category
	* @param $category STRING Research category slug
	* @return Post|false Returns first featured blog post or false if none found
	*/
	public function getFeaturedBlogByCategory($category) {
		// Reuse getFeaturedResearchByCategory but for blog posts
		return $this->getFeaturedResearchByCategory($category, 'blogs');
	}
	
	/**
	* Get posts by research category
	* Example: Get all posts from "Media Ethics" category
	* @param $postType STRING Post type ('research' or 'blogs')
	* @param $category STRING Research category slug
	* @param $extraQuery array Additional WP_Query parameters
	* @return array Array of posts
	*/
	public function getPostByCategory($postType, $category, $extraQuery = []) {
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
	* Example: Create WP_Query tax_query for "Media Ethics" category
	* @param $category STRING Research category slug
	* @return array WP_Query tax_query parameters
	*/
	public function getResearchCategoryQuery($category) {
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
	* Example: Get list of all categories like "Media Ethics", "Case Studies", etc.
	* @return array Array of term objects
	*/
	public function getResearchCategories() {
		return \Timber::get_terms([
			'taxonomy' => 'research-categories',
			'hide_empty' => true,
		]);
	}
	
	/**
	 * Get recent posts with optional filtering
	 */
	public function getRecentPosts($options = []) {
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
	 */
	public function getUpcomingEvents($options = []) {
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
	