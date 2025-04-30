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
 * - Vertical-based URLs
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
		$vars[] = 'vertical_base';
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
		$rules['research/category/media-ethics/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=media-ethics,' . '$matches[1]&is_research_archive=1';
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
	 * - Vertical-based team pages (/team/vertical/[vertical])
	 * - Team categories (/team/category/[category])
	 * - Combined vertical and category pages (/team/vertical/[vertical]/category/[category])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getTeamRewrites()
	{
		$rules = [];
		// vertical only
		$rules['team/vertical/([^/]+)/?$'] = 'index.php?post_type=team&verticals=$matches[1]&orderby=menu_order&order=ASC';

		// team-cats as /team/category/{term}
		$rules['team/category/([^/]+)/?$'] = 'index.php?post_type=team&team_category=$matches[1]&orderby=menu_order&order=ASC';

		// double query. append query name at the end
		// team/vertical/{term}/category/{term}
		$rules['team/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=team&verticals=$matches[1]&team_category=$matches[2]&orderby=menu_order&order=ASC';

		return $rules;
	}

	/**
	 * Generate rewrite rules for Announcement post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - Vertical-based announcement pages (/announcement/vertical/[vertical])
	 * - Announcement categories (/announcement/category/[category])
	 * - Combined vertical and category pages (/announcement/vertical/[vertical]/category/[category])
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
	 * - Vertical-based blog pages (/blogs/vertical/[vertical])
	 * - Blog categories (/blogs/category/[category])
	 * - Combined vertical and category pages (/blogs/vertical/[vertical]/category/[category])
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getBlogRewrites()
	{
		$rules = [];
		// vertical only
		$rules['blogs/vertical/([^/]+)/?$'] = 'index.php?post_type=blogs&verticals=$matches[1]';

		// blogs-categories as /blogs/category/{term}
		$rules['blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&blogs-category=$matches[1]';

		// double query. append query name at the end
		// blogs/vertical/{term}/category/{term}
		$rules['blogs/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=blogs&verticals=$matches[1]&blogs-category=$matches[2]';

		return $rules;
	}

	/**
	 * Generate rewrite rules for Event post type and its taxonomies.
	 *
	 * Handles URL structures for:
	 * - All events page (/events)
	 * - Upcoming events (/events/upcoming)
	 * - Past events (/events/past)
	 * - Vertical-based event pages (/events/vertical/[vertical])
	 * - Event categories (/events/category/[category])
	 * - Combined vertical and category pages (/events/vertical/[vertical]/category/[category])
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

		// vertical only
		$rules['events/vertical/([^/]+)/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&query_name=all_events';

		// event-categories as /event/category/{term}
		$rules['events/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&tribe_events_cat=$matches[1]';

		// double query. append query name at the end
		// event/vertical/{term}/category/{term}
		$rules['events/vertical/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&tribe_events_cat=$matches[2]';

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
		$post_types = ['blogs', 'team', 'board']; // Removed 'announcement' as it has its own special handling

		foreach ($post_types as $post_type) {
			// Single post URLs must come before category URLs
			$rules[$post_type . '/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&name=$matches[1]';
			
			// Category URLs
			$rules[$post_type . '/category/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&category_name=$matches[1]';

			// For subcategories or additional taxonomies
			if ($post_type === 'blogs') {
				// /blogs/[category]/category/[blogs-category]
				$rules[$post_type . '/category/([^/]+)/category/([^/]+)/?$'] = 'index.php?post_type=' . $post_type . '&category_name=$matches[1]&blogs-category=$matches[2]';
			}
		}

		return $rules;
	}

	/**
	 * Generate rewrite rules for vertical-based URLs.
	 *
	 * Creates complex URL structures for content organized by verticals:
	 * - Basic vertical pages (/vertical/[vertical])
	 * - Vertical + post type pages (/vertical/[vertical]/[post-type])
	 * - Vertical + post type + category pages
	 * - Pagination support for various combinations
	 *
	 * @return array Array of rewrite rules.
	 */
	public function getVerticalRewrites()
	{

		/**
		 * Example Post Type Category Rewrite
		 * $postTypeSlug
		 * $taxonomySlug
		 * $rules['vertical/([^/]+)/$postTypeSlug/category/([^/]+)/?$'] = 'index.php?post_type=$postTypeSlug&verticals=$matches[1]&$taxonomySlug=$matches[2]&vertical_base=1';
		 *
		 */
		$rules = [];
		// everything in vertical
		// /vertical/{ verticalTerm }/
		$rules['vertical/([^/]+)/?$'] = 'index.php?verticals=$matches[1]&vertical_base=1';

		// /vertical/{ verticalTerm }/team/
		// needs to go above the generic one since we're making a specific query for this one
		$rules['vertical/([^/]+)/team/?$'] = 'index.php?post_type=team&verticals=$matches[1]&vertical_base=1&orderby=menu_order&order=ASC';

		// tribe_Events
		// needs to go above the generic one since we're making a specific query for this one
		$rules['vertical/([^/]+)/events/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&vertical_base=1';


		// vertical with a specific post type
		// /vertical/{ verticalTerm }/type/{ postType }
		$rules['vertical/([^/]+)/([^/]+)/?$'] = 'index.php?post_type=$matches[2]&verticals=$matches[1]&vertical_base=1';

		// research-cats as
		// /vertical/{ verticalTerm }/research/category/{ term }
		// $rules['vertical/([^/]+)/research/category/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-categories=$matches[2]';

		// $rules['vertical/([^/]+)/research/tag/([^/]+)/?$'] = 'index.php?post_type=research&verticals=$matches[1]&research-tags=$matches[2]';

		// /vertical/{ verticalTerm }/team/category/{ term }
		$rules['vertical/([^/]+)/team/category/([^/]+)/?$'] = 'index.php?post_type=team&verticals=$matches[1]&team_category=$matches[2]&vertical_base=1&orderby=menu_order&order=ASC';

		// announcement category
		// /vertical/{ verticalTerm }/announcement/category/{ term }
		// $rules['vertical/([^/]+)/announcement/category/([^/]+)/?$'] = 'index.php?post_type=announcement&verticals=$matches[1]&announcement-category=$matches[2]&vertical_base=1';

		// blogs category
		// /vertical/{ verticalTerm }/blogs/category/{ term }
		$rules['vertical/([^/]+)/blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&verticals=$matches[1]&blogs-category=$matches[2]&vertical_base=1';

		$rules['vertical/([^/]+)/events/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&verticals=$matches[1]&tribe_events_cat=$matches[2]&vertical_base=1';

		// post category as/vertical/{ verticalTerm }/post/tag/{ term }
		$rules['vertical/([^/]+)/post/category/([^/]+)/?$'] = 'index.php?post_type=post&verticals=$matches[1]&category_name=$matches[2]&vertical_base=1';


		$rules['vertical/([^/]+)/post/tag/([^/]+)/?$'] = 'index.php?post_type=post&verticals=$matches[1]&tag=$matches[2]&vertical_base=1';

		// post category paginated as/vertical/{ verticalTerm }/post/tag/{ term }/page/{ page number}
		$rules['vertical/([^/]+)/post/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=post&verticals=$matches[1]&category_name=$matches[2]&paged=$matches[3]&vertical_base=1';

		$rules['vertical/([^/]+)/post/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=post&verticals=$matches[1]&tag=$matches[2]&paged=$matches[3]&vertical_base=1';

		// post category paginated as/vertical/{ verticalTerm }/research/tag/{ term }/page/{ page number}
		// OLD: $rules['vertical/([^/]+)/research/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&verticals=$matches[1]&category_name=$matches[2]&paged=$matches[3]&vertical_base=1';
		$rules['category/([^/]+)/research/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&category_name=$matches[1]&research-categories=$matches[2]&paged=$matches[3]&vertical_base=1';

		// OLD: $rules['vertical/([^/]+)/research/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&verticals=$matches[1]&tag=$matches[2]&paged=$matches[3]&vertical_base=1';
		$rules['category/([^/]+)/research/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&category_name=$matches[1]&research-tags=$matches[2]&paged=$matches[3]&vertical_base=1';

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
			$this->getVerticalRewrites() +
			$this->getPostTypeCategoryRewrites() +
			$wp_rewrite->rules;
	}
}
