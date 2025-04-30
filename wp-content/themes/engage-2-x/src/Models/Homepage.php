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
		$this->funders = Timber::get_posts(
			['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
		);
	}
	
	// Function to get the most recent posts from each research category
	public function getRecent() {
		// Get an array of all of the research categories
		$categories = $this->Query->getResearchCategories();
		$this->recent = []; // Set to an empty array to allow array_merge
		$this->moreRecent = [];
		$this->allQueriedPosts = []; // Keeps track of all previously queried posts to avoid duplicates
		
		foreach ($categories as $category) {
			$categoryName = $category->slug;
			// Get the most recent post and moreResearch posts for that specific category
			$results = $this->getRecentFeaturedResearch($categoryName);
			$this->recent = array_merge($results, $this->recent);
			$this->allQueriedPosts = array_merge($results, $this->allQueriedPosts);
			
			$results = $this->getMoreRecentResearch($this->recent, $categoryName);
			$this->moreRecent = array_merge($results, $this->moreRecent);
			$this->allQueriedPosts = array_merge($results, $this->allQueriedPosts);
		}
		
		// Limit the total number of posts to 12
		$this->recent = array_slice($this->recent, 0, 12);
		$this->moreRecent = array_slice($this->moreRecent, 0, 12);
		$this->allQueriedPosts = array_slice($this->allQueriedPosts, 0, 12);
		
		// $this->sortByDate(true);
		$this->sortByDate(false);
		
		$this->sortSliderByTopFeatured();
	}
	
	//get the most recent featured research
	public function getRecentFeaturedResearch($categoryName) {
		$featuredPosts = $this->queryPosts(true, $categoryName, 1);
		$recentFeaturedPosts = array();
		//only show one featured research per category
		foreach ($featuredPosts as $featurePost) {
			array_push($recentFeaturedPosts, $featurePost);
			break;
		}
		return $recentFeaturedPosts;
	}
	
	//get the more research posts
	public function getMoreRecentResearch($featuredSliderPosts, $categoryName) {
		// how many more_research_posts should be display on the home page for each category
		$numFeaturedPerCategory = [
			"journalism" => 3,
		];
		
		$num_posts = array_key_exists($categoryName, $numFeaturedPerCategory) ?
		$numFeaturedPerCategory[$categoryName] : 8;
		
		$allRecentResearch = $this->queryPosts(false, $categoryName, $num_posts);
		$allRecentResearchArray = $allRecentResearch->to_array();
		
		return $allRecentResearchArray;
	}
	
	// query the posts with the given arguments
	public function queryPosts($is_featured, $categoryName, $numberOfPosts) {
		$args = [
			'postType' => 'research',
			'research-categories' => $categoryName,
			'postsPerPage' => $numberOfPosts,
			'post__not_in' => array_map(function ($post) {
				return $post->id;
			}, $this->allQueriedPosts)
		];
		if ($is_featured) {
			// add extraQuery if want to get only posts that are marked by the admin to "show"
			// in the featured_research custom field
			
			$args['extraQuery'] = [
				'meta_query' => [
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
					],
				];
			}
			return $this->Query->getRecentPosts($args);
		}
		
		// sort by the date
		public function sortByDate($is_slider) {
			if ($is_slider) {
				usort($this->recent, function ($a, $b) {
					return strtotime($b->post_date) - strtotime($a->post_date);
				});
			} else {
				usort(
					$this->moreRecent,
					function ($a, $b) {
						return strtotime($b->post_date) - strtotime($a->post_date);
					}
				);
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
	