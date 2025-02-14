<?php
namespace Engage\Models;

use Engage\Managers\Queries as Queries;
use Timber;

class Homepage {
	public $funders;
	public $Query;
	public $recent;
	public $moreRecent;
	public $allQueriedPosts;
	
	public function __construct() {
		$this->Query = new Queries();
		$this->setFunders();
		$this->getRecent();
	}
	
	public function setFunders() {
		$this->funders = Timber::get_posts([
			'post_type' => 'funders',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		]);
	}
	
	// Function to get the most recent posts across all categories
	public function getRecent() {
		$this->recent = []; // Set to an empty array to allow array_merge
		$this->moreRecent = [];
		$this->allQueriedPosts = []; // Keeps track of all previously queried posts to avoid duplicates
		
		// Get featured research posts
		$results = $this->getRecentFeaturedResearch();
		$this->recent = array_merge($results, $this->recent);
		$this->allQueriedPosts = array_merge($results, $this->allQueriedPosts);
		
		// Get more recent research posts
		$results = $this->getMoreRecentResearch($this->recent);
		$this->moreRecent = array_merge($results, $this->moreRecent);
		$this->allQueriedPosts = array_merge($results, $this->allQueriedPosts);
		
		$this->sortByDate(false);
		$this->sortSliderByTopFeatured();
	}
	
	// Get the most recent featured research
	public function getRecentFeaturedResearch() {
		$featuredPosts = $this->queryPosts(true, 1);
		$recentFeaturedPosts = array();
		
		// Only show one featured research post
		foreach ($featuredPosts as $featurePost) {
			array_push($recentFeaturedPosts, $featurePost);
			break;
		}
		return $recentFeaturedPosts;
	}
	
	// Get the more research posts
	public function getMoreRecentResearch($featuredSliderPosts) {
		// How many more_research_posts should be displayed on the home page
		$num_posts = 3;
		
		$allRecentResearch = $this->queryPosts(false, $num_posts);
		return $allRecentResearch->to_array();
	}
	
	// Query the posts with the given arguments
	public function queryPosts($is_featured, $numberOfPosts) {
		$args = [
			'post_type' => 'research',
			'posts_per_page' => $numberOfPosts,
			'post__not_in' => array_map(function ($post) {
				return $post->id;
			}, $this->allQueriedPosts)
		];
		
		if ($is_featured) {
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'key' => 'featured_research',
					'value' => serialize(array('Show')),
					'compare' => 'LIKE'
				],
				[
					'key' => 'featured_research',
					'value' => serialize(array('Showpost')),
					'compare' => 'LIKE'
				]
			];
		}
		
		return $this->Query->getRecentPosts($args);
	}
	
	// Sort by the date
	public function sortByDate($is_slider) {
		if ($is_slider) {
			usort($this->recent, function ($a, $b) {
				return strtotime($b->post_date) - strtotime($a->post_date);
			});
		} else {
			usort($this->moreRecent, function ($a, $b) {
				return strtotime($b->post_date) - strtotime($a->post_date);
			});
		}
	}
	
	public function sortSliderByTopFeatured() {
		usort($this->recent, function ($a, $b) {
			$topFeatureA = is_array($a->top_featured_research) ? implode('', $a->top_featured_research) : $a->top_featured_research;
			$topFeatureB = is_array($b->top_featured_research) ? implode('', $b->top_featured_research) : $b->top_featured_research;
			return strcmp($topFeatureB, $topFeatureA);
		});
	}
}
	