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
		
		// Define our primary categories (former verticals)
		// These categories MUST appear first in the URL
		$primary_categories = [
			'bridging-divides',
			'journalism',
			'media-ethics',
			'social-platforms',
			'propaganda',
			'science-communication'
		];
		
		// Create the pattern for primary categories only
		$primary_pattern = '(' . implode('|', $primary_categories) . ')';
		
		// Primary category + research + subcategory URL structure
		// Example: /media-ethics/research/category/case-studies/
		$rules[$primary_pattern . '/research/category/([^/]+)/?$'] = 
			'index.php?post_type=research&research-categories=$matches[1]&subcategory=$matches[2]';
		
		// Ensure our rules take precedence in the rewrite rules array
		add_filter('rewrite_rules_array', function($rules) {
			$new_rules = $this->getCategoryBaseRewrites();
			return $new_rules + $rules;
		}, 1);
		
		// Modify the main query to handle our custom URL structure
		add_filter('pre_get_posts', function($query) {
			if (!is_admin() && $query->is_main_query() && 
				$query->get('post_type') === 'research' && 
				$query->get('research-categories')) {
				
				$primary_cat = $query->get('research-categories');
				$sub_cat = $query->get('subcategory');
				
				if ($primary_cat && $sub_cat) {
					// Set up a tax query that requires posts to be in BOTH categories
					$query->set('tax_query', array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'research-categories',
							'field'    => 'slug',
							'terms'    => $primary_cat
						),
						array(
							'taxonomy' => 'research-categories',
							'field'    => 'slug',
							'terms'    => $sub_cat
						)
					));
				}
			}
			return $query;
		});
		
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
