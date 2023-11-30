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
	* Get a featured post from a specific post and vertical
	* @param $vertical STRING
	* @param $postType = 'research' or 'blogs'. They both use the same info so we're reusing this function
	*/
	public function getFeaturedResearchByVertical($vertical, $postType = 'research') {
		$class = 'Engage\Models\ResearchArticle';
		$posts = $this->getPostByVertical($postType, $vertical, $this->getFeaturedResearchMetaQuery(), $class);
		
		if(empty($posts)) {
			// run the query again, but without the featured research
			$posts =$this->getPostByVertical($postType, $vertical, [], $class);
		}
		// if it's not empty, return the first post
		return ( empty($posts) ? $posts[0] : false);
	}
	
	/**
	* Uses getFeaturedResearchByVertical()
	*
	*/
	public function getFeaturedBlogByVertical($vertical) {
		return $this->getFeaturedResearcByVertical($vertical, 'blogs');
	}
	
	
	public function getPostByVertical($postType, $vertical, $extraQuery = [], $class = 'Engage\Models\Article') {
		$query = array_merge([
			'post_type'     => $postType,
			'posts_per_page'  => 1
		], $this->getVerticalTaxQuery($vertical));
		
		$query = array_merge($query, $extraQuery);
		
		
		return Timber::get_posts($query, $class);
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
		
		public function getVerticalTaxQuery($vertical) {
			return ['tax_query'     => [
				[
					'taxonomy' => 'verticals',
					'field'    => 'slug',
					'terms'    => $vertical
					]
					]
				];
			}
			
			public function getVerticals() {
				return \Timber::get_terms([
					'taxonomy' => 'verticals',
					'hide_empty' => true,
				]);
			}
			
			public function getRecentPosts($options = []) {
				
				$defaults = [
					'postType' 		=> 'any',
					'postsPerPage' 	=> 10,
					'vertical' 		=> false,
					'class' 		=> 'Engage\Models\Article',
					'extraQuery' 	=> [],
					'post__not_in' => []
				];
				$options = array_merge($defaults, $options);
				$query = array_merge([
					'post_type'     => $options['postType'],
					'posts_per_page'  => $options['postsPerPage'],
					'post__not_in' => $options['post__not_in']
				], $options['extraQuery']);
				
				if($options['vertical'] !== false) {
					// var_dump( 'vertical false' );
					$query = array_merge($query, $this->getVerticalTaxQuery($options['vertical']));
				}
				// var_dump( $query );
				$posts = Timber::get_posts($query);
				return $posts;
			}
			
			public function getUpcomingEvents($options = []) {
				$defaults = [
					'postType'		=> 'tribe_events',
					'postsPerPage' 	=> 10,
					'vertical' 		=> false,
					'class' 		=> 'Engage\Models\Event',
					'extraQuery' 	=> []
				];
				$options = array_merge($defaults, $options);
				
				return $this->getRecentPosts($options);
			}
		}
		