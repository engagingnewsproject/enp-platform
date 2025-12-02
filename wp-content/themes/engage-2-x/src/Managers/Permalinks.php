<?php

/**
 * Permalink Manager for custom URL structures and rewrite rules.
 *
 * This class manages custom permalink structures and rewrite rules for various
 * post types and taxonomies in the WordPress site. It handles:
 * - Research categories and subcategories
 * - Team archives and categories
 * - Announcement archives
 * - Blog archives
 * - Event archives
 *
 * @package Engage\Managers
 */

namespace Engage\Managers;

class Permalinks
{

	/**
	 * Constructor for the Permalinks class.
	 */
	public function __construct() {}

	/**
	 * Initialize the permalink manager by registering necessary WordPress hooks.
	 *
	 * @return void
	 */
	public function run()
	{
		// Add actions to WordPress hooks
		add_action('query_vars', [$this, 'addQueryVars']);
		add_filter('generate_rewrite_rules', [$this, 'addRewrites']);
		
		// Add media ethics subcategory pagination rule with top priority
		add_action('init', [$this, 'addMediaEthicsPaginationRule'], 5);
		
		// Force WordPress to recognize media ethics subcategory queries (early, before 404 is set)
		add_filter('request', [$this, 'handleMediaEthicsSubcategoryRequest'], 1);
		
		// Prevent 404 for media ethics subcategory queries
		add_action('pre_get_posts', [$this, 'prevent404ForMediaEthicsSubcategory'], 1);

		// Add the term_link filter for research categories
		add_filter('term_link', [$this, 'filterResearchCategoryTermLink'], 10, 3);
	}
	
	/**
	 * Handle media ethics subcategory queries to prevent 404 errors
	 * 
	 * When WordPress sees comma-separated taxonomy values, it might not
	 * recognize them as valid queries. This ensures they're treated correctly.
	 * 
	 * @param array $query_vars Query variables
	 * @return array Modified query variables
	 */
	public function handleMediaEthicsSubcategoryRequest($query_vars)
	{
		$research_categories = isset($query_vars['research-categories']) ? $query_vars['research-categories'] : '';
		
		// Check if this is a media ethics subcategory query
		if (!empty($research_categories) && is_string($research_categories)) {
			$categories = explode(',', $research_categories);
			if (in_array('media-ethics', $categories) && count($categories) > 1) {
				// Set query vars to ensure WordPress recognizes this as a valid query
				$query_vars['taxonomy'] = 'research-categories';
				$query_vars['post_type'] = 'research';
				// Ensure post_type is set so WordPress knows this is a valid archive
				if (empty($query_vars['post_type'])) {
					$query_vars['post_type'] = 'research';
				}
			}
		}
		
		return $query_vars;
	}
	
	/**
	 * Prevent 404 errors for media ethics subcategory queries
	 * 
	 * WordPress might set is_404 if it doesn't find posts, but we handle
	 * the query ourselves in the archive template. We need to set up the
	 * query properly so WordPress recognizes it as valid.
	 */
	public function prevent404ForMediaEthicsSubcategory($query)
	{
		if (!$query->is_main_query() || is_admin()) {
			return;
		}
		
		$research_categories = get_query_var('research-categories');
		
		if (!empty($research_categories) && is_string($research_categories)) {
			$categories = explode(',', $research_categories);
			if (in_array('media-ethics', $categories) && count($categories) > 1) {
				// Set up the query to prevent 404
				$query->set('post_type', 'research');
				$query->set('taxonomy', 'research-categories');
				
				// Prevent WordPress from setting 404
				add_action('wp', function() use ($query) {
					$query->is_404 = false;
					$query->is_tax = true;
					$query->is_archive = true;
					$query->is_home = false;
					$query->is_singular = false;
				}, 1);
			}
		}
	}
	
	/**
	 * Add pagination rewrite rule for media ethics subcategories with top priority
	 * 
	 * This ensures the pagination rule is matched before other conflicting rules
	 */
	public function addMediaEthicsPaginationRule()
	{
		add_rewrite_rule(
			'^research/category/media-ethics/([^/]+)/page/?([0-9]{1,})/?$',
			'index.php?post_type=research&research-categories=media-ethics,$matches[1]&paged=$matches[2]&is_research_archive=1',
			'top'
		);
	}

	/**
	 * Add custom query variables to WordPress.
	 *
	 * @param array $vars Existing query variables.
	 * @return array Modified query variables.
	 */
	public function addQueryVars($vars)
	{
		// Add custom query variables
		$vars[] = 'query_name';
		$vars[] = 'is_research_archive';
		return $vars;
	}

	/**
	 * Generate rewrite rules for Research post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - Media ethics subcategories (/research/media-ethics/[subcategory])
	 * - General research categories (/research/[category])
	 * - Single research posts (/research/post/[slug])
	 * - Research tags (/research/tag/[tag])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getResearchRewrites()
	{
		$rules = [];

		// Base research archive
		$rules['research/?$'] = 'index.php?post_type=research&is_research_archive=1';
		$rules['research/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&is_research_archive=1&paged=$matches[1]';

		// Media ethics subcategory pages (most specific first)
		// Pagination support for media ethics subcategories
		$rules['research/category/media-ethics/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&research-categories=media-ethics,$matches[1]&paged=$matches[2]&is_research_archive=1';
		$rules['research/category/media-ethics/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=media-ethics,$matches[1]&is_research_archive=1';
		$rules['research/category/media-ethics/?$'] = 'index.php?post_type=research&research-categories=media-ethics&is_research_archive=1';

		// Research category archives
		// example: /research/category/media-ethics
		$rules['research/category/([^/]+)/?$'] = 'index.php?research-categories=$matches[1]&is_research_archive=1';
		$rules['research/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?research-categories=$matches[1]&paged=$matches[2]&is_research_archive=1';

		// Single post URLs (no /category/ prefix means it's a post)
		$rules['research/([^/]+)/?$'] = 'index.php?post_type=research&name=$matches[1]';

		// Research tags
		$rules['research/tag/([^/]+)/?$'] = 'index.php?post_type=research&research-tags=$matches[1]';
		$rules['research/category/([^/]+)/tag/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=$matches[1]&research-tags=$matches[2]';

		return $rules;
	}

	/**
	 * Generate rewrite rules for Team post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - Team categories (/team/category/[category])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getTeamRewrites()
	{
		$rules = [];

		// team-cats as /team/category/{term}
		$rules['team/category/([^/]+)/?$'] = 'index.php?post_type=team&team_category=$matches[1]&orderby=menu_order&order=ASC';

		return $rules;
	}

	/**
	 * Generate rewrite rules for Announcement post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - Announcement categories (/announcement/category/[category])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getAnnouncementRewrites()
	{
		$rules = [];
		
		// announcement-cats as /announcement/category/{term}
		$rules['announcement/category/([^/]+)/?$'] = 'index.php?post_type=announcement&announcement-category=$matches[1]';
		
		// Pagination support
		$rules['announcement/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=announcement&announcement-category=$matches[1]&paged=$matches[2]';
		
		return $rules;
	}

	/**
	 * Generate rewrite rules for Blog post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - Blog categories (/blogs/category/[category])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getBlogRewrites()
	{
		$rules = [];

		// blogs-categories as /blogs/category/{term}
		$rules['blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&blogs-category=$matches[1]';

		return $rules;
	}

	/**
	 * Generate rewrite rules for Event post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - All events page (/events)
	 * - Upcoming events (/events/upcoming)
	 * - Past events (/events/past)
	 * - Event categories (/events/category/[category])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getEventsRewrites()
	{
		$rules = [];
		// this displays ALL upcoming and past events using eventDisplay=custom
		$rules['events/?$'] = 'index.php?post_type=tribe_events&query_name=all_events';

		// tribe defaults to only using upcoming events
		// order by whichever has the closest start date to today
		$rules['events/upcoming/?$'] = 'index.php?post_type=tribe_events&meta_key=_EventStartDate&orderby=_EventStartDate&order=ASC&query_name=upcoming_events';

		$rules['events/past/?$'] = 'index.php?post_type=tribe_events&query_name=past_events';

		// event-categories as /event/category/{term}
		$rules['events/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&tribe_events_cat=$matches[1]';

		return $rules;
	}

	/**
	 * Generate rewrite rules for post type category URLs.
	 *
	 * Creates URL structures for various post types that support categories:
	 * - Blogs
	 * - Team
	 * - Board
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getPostTypeCategoryRewrites()
	{
		$rules = [];

		// Define post types that can have categories
		$post_types = ['team', 'board']; // Removed 'blogs' as it has its own special handling

		foreach ($post_types as $post_type) {
			// Single post URLs must come before category URLs
			$rules[$post_type . '/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&name=$matches[1]';
			
			// Category URLs
			$rules[$post_type . '/category/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&category_name=$matches[1]';
		}

		return $rules;
	}

	/**
	 * Add all rewrite rules to WordPress.
	 *
	 * Combines rewrite rules from all post types and adds them to WordPress's
	 * rewrite rules array. Rules are added in order of specificity to ensure
	 * proper URL matching.
	 *
	 * @param object $wp_rewrite WordPress rewrite object.
	 * @return void
	 */
	public function addRewrites($wp_rewrite)
	{
		// Combine all rewrite rules for different post types
		$wp_rewrite->rules =
			$this->getTeamRewrites() +
			$this->getAnnouncementRewrites() +
			$this->getBlogRewrites() +
			$this->getEventsRewrites() +
			$this->getResearchRewrites() +
			$this->getPostTypeCategoryRewrites() +
			$wp_rewrite->rules;
	}

	/**
	 * Filter the term link for research categories to use the custom structure.
	 */
	public function filterResearchCategoryTermLink($url, $term, $taxonomy)
	{
		if ($taxonomy === 'research-categories') {
			$url = home_url('/research/category/' . $term->slug . '/');
		}
		return $url;
	}
}
