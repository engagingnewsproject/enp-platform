<?php
/*
* Modifications to permalinks
*/
namespace Engage\Managers;

class Permalinks {
	
	public function __construct() {
	}
	
	public function run() {
		add_action('query_vars', [$this, 'addQueryVars']);
		add_filter('generate_rewrite_rules', [$this, 'addRewrites']);
	}
	
	public function addQueryVars($vars) {
		$vars[] = 'query_name';
		$vars[] = 'category_base';  // Added for category-based routing
		return $vars;
	}
	
	public function getResearchRewrites() {
		$rules = [];
		// research-cats as /research/category/{term}
		$rules['research/category/([^/]+)/?$'] = 'index.php?post_type=research&research-categories=$matches[1]';
		
		// research-tags as /research/tag/{term}
		$rules['research/tag/([^/]+)/?$'] = 'index.php?post_type=research&research-tags=$matches[1]';
		
		return $rules;
	}
	
	public function getTeamRewrites() {
		$rules = [];
		// team-cats as /team/category/{term}
		$rules['team/category/([^/]+)/?$'] = 'index.php?post_type=team&team_category=$matches[1]&orderby=menu_order&order=ASC';
		
		return $rules;
	}
	
	public function getAnnouncementRewrites() {
		$rules = [];
		// announcement-cats as /announcement/category/{term}
		$rules['announcement/category/([^/]+)/?$'] = 'index.php?post_type=announcement&announcement-category=$matches[1]';
		
		return $rules;
	}
	
	public function getBlogRewrites() {
		$rules = [];
		// blogs-categories as /blogs/category/{term}
		$rules['blogs/category/([^/]+)/?$'] = 'index.php?post_type=blogs&blogs-category=$matches[1]';
		
		return $rules;
	}
	
	public function getEventsRewrites() {
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
	
	public function getCategoryRewrites() {
		$rules = [];
		
		// Post category and tag rewrites
		$rules['post/category/([^/]+)/?$'] = 'index.php?post_type=post&category_name=$matches[1]';
		$rules['post/tag/([^/]+)/?$'] = 'index.php?post_type=post&tag=$matches[1]';
		
		// Post category/tag pagination
		$rules['post/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=post&category_name=$matches[1]&paged=$matches[2]';
		$rules['post/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=post&tag=$matches[1]&paged=$matches[2]';
		
		// Research category/tag pagination
		$rules['research/category/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&category_name=$matches[1]&paged=$matches[2]';
		$rules['research/tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=research&tag=$matches[1]&paged=$matches[2]';
		
		return $rules;
	}
	
	public function getCategoryBaseRewrites() {
		$rules = [];
		// Base category view
		$rules['([^/]+)/?$'] = 'index.php?category_name=$matches[1]&category_base=1';
		
		// Category with post type
		$rules['([^/]+)/research/?$'] = 'index.php?category_name=$matches[1]&post_type=research&category_base=1';
		$rules['([^/]+)/team/?$'] = 'index.php?category_name=$matches[1]&post_type=team&category_base=1&orderby=menu_order&order=ASC';
		$rules['([^/]+)/blogs/?$'] = 'index.php?category_name=$matches[1]&post_type=blogs&category_base=1';
		$rules['([^/]+)/announcement/?$'] = 'index.php?category_name=$matches[1]&post_type=announcement&category_base=1';
		$rules['([^/]+)/events/?$'] = 'index.php?category_name=$matches[1]&post_type=tribe_events&category_base=1';
		
		// Category with post type and subcategory
		$rules['([^/]+)/research/category/([^/]+)/?$'] = 'index.php?category_name=$matches[1]&post_type=research&research-categories=$matches[2]&category_base=1';
		$rules['([^/]+)/team/category/([^/]+)/?$'] = 'index.php?category_name=$matches[1]&post_type=team&team_category=$matches[2]&category_base=1&orderby=menu_order&order=ASC';
		$rules['([^/]+)/blogs/category/([^/]+)/?$'] = 'index.php?category_name=$matches[1]&post_type=blogs&blogs-category=$matches[2]&category_base=1';
		$rules['([^/]+)/announcement/category/([^/]+)/?$'] = 'index.php?category_name=$matches[1]&post_type=announcement&announcement-category=$matches[2]&category_base=1';
		$rules['([^/]+)/events/category/([^/]+)/?$'] = 'index.php?category_name=$matches[1]&post_type=tribe_events&tribe_events_cat=$matches[2]&category_base=1';
		
		return $rules;
	}
	
	public function addRewrites($wp_rewrite) {
		// Add category base rewrites first (higher priority)
		$wp_rewrite->rules = $this->getCategoryBaseRewrites() +
			$this->getTeamRewrites() +
			$this->getAnnouncementRewrites() +
			$this->getBlogRewrites() +
			$this->getEventsRewrites() +
			$this->getResearchRewrites() +
			$this->getCategoryRewrites() +
			$wp_rewrite->rules;
	}
}
