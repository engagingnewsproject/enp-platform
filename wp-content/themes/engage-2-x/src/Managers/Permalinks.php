<?php
/*
* WordPress Rewrite Rules Manager
* 
* Registers custom URL structures for different post types and their taxonomies.
* For example: /research/category/bridging-divides/
*/

namespace Engage\Managers;

class Permalinks
{
	public function __construct() {}

	public function run()
	{
		add_action('query_vars', [$this, 'addQueryVars']);
		add_filter('generate_rewrite_rules', [$this, 'addRewrites']);
	}

	public function addQueryVars($vars)
	{
		$vars[] = 'query_name';
		return $vars;
	}

	public function getResearchRewrites()
	{
		$rules = [];

		// Basic category and tag URLs
		$rules['research/category/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=$matches[1]';
		$rules['research/tag/([^/]+)/?$'] = 'index.php?post_type=research&research-tags=$matches[1]';

		// Add pagination support
		$rules['research/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&research-categories=$matches[1]&paged=$matches[2]';
		$rules['research/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&research-tags=$matches[1]&paged=$matches[2]';

		return $rules;
	}

	public function getTeamRewrites()
	{
		$rules = [];
		$rules['team/category/([^/]+)/?$'] = 'index.php?post_type=team&team_category=$matches[1]&orderby=menu_order&order=ASC';
		return $rules;
	}

	public function getAnnouncementRewrites()
	{
		$rules = [];
		$rules['announcement/category/([^/]+)/?$'] = 'index.php?post_type=announcement&announcement-category=$matches[1]';
		return $rules;
	}

	public function getBlogRewrites()
	{
		$rules = [];
		$rules['blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&blogs-category=$matches[1]';
		return $rules;
	}

	public function getEventsRewrites()
	{
		$rules = [];
		// Basic event URLs
		$rules['events/?$'] = 'index.php?post_type=tribe_events&query_name=all_events';
		$rules['events/upcoming/?$'] = 'index.php?post_type=tribe_events&meta_key=_EventStartDate&orderby=_EventStartDate&order=ASC&query_name=upcoming_events';
		$rules['events/past/?$'] = 'index.php?post_type=tribe_events&query_name=past_events';

		// Event categories
		$rules['events/category/([^/]+)/?$'] = 'index.php?post_type=tribe_events&tribe_events_cat=$matches[1]';

		return $rules;
	}

	public function addRewrites($wp_rewrite)
	{
		// Combine all rewrite rules for different post types
		$wp_rewrite->rules = $this->getTeamRewrites() +
			$this->getAnnouncementRewrites() +
			$this->getBlogRewrites() +
			$this->getEventsRewrites() +
			$this->getResearchRewrites() +
			$wp_rewrite->rules;
	}
}
