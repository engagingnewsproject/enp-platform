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
	
	public function getFeaturedResearchMetaQuery() {
		return ['meta_query' => [
			[
				'key'     => 'featured_research',
				'value'   => 'a:1:{i:0;s:8:"Showpost";}', // <--- ugh. That's how it's stored in the DB though.
				'compare' => '=',
				]
				]
			];
	}
	
	public function getResearchCategories() {
		return \Timber::get_terms([
			'taxonomy' => 'research-categories',
			'hide_empty' => true,
		]);
	}
	
	public function getRecentPosts($args) {
		return \Timber::get_posts($args);
	}
}