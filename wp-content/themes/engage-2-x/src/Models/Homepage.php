<?php

namespace Engage\Models;

use Engage\Managers\Queries as Queries;
use Timber;

/**
 * Homepage Model
 * 
 * This class manages the homepage content, specifically handling:
 * - Featured research posts that appear in the slider
 * - Additional research posts from different categories
 * - Funder information
 * 
 * The homepage shows a mix of featured and recent research posts, ensuring:
 * - No duplicate posts appear
 * - Posts are properly sorted by date and featured status
 * - A good mix of content from different research categories
 */
class Homepage
{
	public $funders;
	public $Query;
	public $recent;
	public $moreRecent;
	public $allQueriedPosts;

	public function __construct()
	{
		$this->Query = new Queries();
		$this->setFunders();
		$this->getRecent();
	}

	/**
	 * Sets up the funders section by fetching all funder posts
	 * 
	 * This gets all the funder posts from WordPress and orders them
	 * according to their menu order (drag-and-drop order in admin)
	 */
	public function setFunders()
	{
		$this->funders = Timber::get_posts(
			['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
		);
	}

	/**
	 * Gets the most recent posts from each research category
	 * 
	 * This is the main function that populates the homepage content:
	 * 1. Gets posts from each research category
	 * 2. Ensures no duplicate posts appear
	 * 3. Limits total posts to 8
	 * 4. Sorts posts by date and featured status
	 */
	public function getRecent()
	{
		// Only pull posts from these research categories
		$allowed_slugs = [
			'bridging-divides',
			'journalism',
			'propaganda',
			'social-platforms',
			'science-communication',
		];

		// 1. Get excluded category IDs from ACF (adjust the field name if needed)
		$excluded_ids = \get_field('more_research');
		if (isset($excluded_ids['exclude_research_category'])) {
			$excluded_ids = $excluded_ids['exclude_research_category'];
		} else {
			$excluded_ids = [];
		}

		// 2. Convert IDs to slugs
		$excluded_slugs = [];
		if (!empty($excluded_ids)) {
			foreach ($excluded_ids as $cat_id) {
				$term = \get_term($cat_id, 'research-categories');
				if ($term && !is_wp_error($term)) {
					$excluded_slugs[] = $term->slug;
				}
			}
		}
		// 3. Remove excluded slugs from allowed_slugs
		$allowed_slugs = array_diff($allowed_slugs, $excluded_slugs);

		$categories = $this->Query->getResearchCategories();
		$this->recent = [];
		$this->moreRecent = [];
		$this->allQueriedPosts = [];

		foreach ($categories as $category) {
			error_log('Category: ' . $category->slug);
			if (!in_array($category->slug, $allowed_slugs)) {
				continue;
			}

			$categoryName = $category->slug;
			$results = $this->getRecentFeaturedResearch($categoryName);
			foreach ($results as $post) {
				error_log('Recent Featured: ' . $post->post_title . ' (ID: ' . $post->ID . ')');
			}
			$this->recent = array_merge($results, $this->recent);
			$this->allQueriedPosts = array_merge($results, $this->allQueriedPosts);
		}

		// Collect the IDs of all slider posts
		$slider_ids = array_map(function($post) {
			// Timber\Post objects use $post->ID, but sometimes $post->id
			return isset($post->ID) ? $post->ID : $post->id;
		}, $this->recent);

		error_log('Slider IDs: ' . implode(', ', $slider_ids));

		// 3. Query for the 8 most recent posts across all allowed categories, excluding slider posts
		$tax_query = [
			[
				'taxonomy' => 'research-categories',
				'field'    => 'slug',
				'terms'    => $allowed_slugs,
				'operator' => 'IN',
			]
		];

		if (!empty($excluded_slugs)) {
			$tax_query[] = [
				'taxonomy' => 'research-categories',
				'field'    => 'slug',
				'terms'    => $excluded_slugs,
				'operator' => 'NOT IN',
			];
		}

		$args = [
			'post_type' => 'research',
			'posts_per_page' => 8,
			'orderby' => 'date',
			'order' => 'DESC',
			'post__not_in' => $slider_ids,
			'tax_query' => $tax_query,
		];
		$this->moreRecent = Timber::get_posts($args);

		// Ensure $this->moreRecent is an array before sorting
		if ($this->moreRecent instanceof \Timber\PostQuery) {
			$this->moreRecent = $this->moreRecent->to_array();
		}

		// $this->sortByDate(true);
		$this->sortByDate(false);

		$this->sortSliderByTopFeatured();

		foreach ($this->moreRecent as $post) {
			error_log('Grid Post: ' . $post->post_title . ' (ID: ' . $post->ID . ')');
		}
	}

	/**
	 * Gets the most recent featured research post for a category
	 * 
	 * For each research category, this gets one featured post that
	 * has been marked as "Show" or "Showpost" in the admin panel
	 * 
	 * @param string $categoryName The slug of the research category
	 * @return array Array containing one featured post
	 */
	public function getRecentFeaturedResearch($categoryName)
	{
		// Get the most recent post, regardless of featured status
		$recentPosts = $this->queryPosts(false, $categoryName, 1);
		$recentFeaturedPosts = array();
		foreach ($recentPosts as $post) {
			array_push($recentFeaturedPosts, $post);
			break;
		}
		return $recentFeaturedPosts;
	}

	/**
	 * Gets additional recent research posts for a category
	 * 
	 * Gets more posts from a specific category to show on the homepage:
	 * - Default is 8 posts per category
	 * - Journalism category gets 3 posts
	 * - Excludes posts already shown in the featured slider
	 * 
	 * @param array $featuredSliderPosts Posts already in the featured slider
	 * @param string $categoryName The slug of the research category
	 * @return array Array of recent research posts
	 */
	public function getMoreRecentResearch($featuredSliderPosts, $categoryName)
	{
		// how many more_research_posts should be display on the home page for each category
		$numFeaturedPerCategory = [
			"journalism" => 3,
		];

		$num_posts = array_key_exists($categoryName, $numFeaturedPerCategory) ?
			$numFeaturedPerCategory[$categoryName] : 8;

		$allRecentResearch = $this->queryPosts(false, $categoryName, $num_posts);
		$allRecentResearchArray = $allRecentResearch->to_array();

		foreach ($allRecentResearch as $post) {
			error_log('More Research: ' . $post->post_title . ' (ID: ' . $post->ID . ', Date: ' . $post->post_date . ')');
		}

		return $allRecentResearchArray;
	}

	/**
	 * Queries WordPress for research posts with specific criteria
	 * 
	 * This is the main query function that gets posts from WordPress:
	 * - Can get either featured or non-featured posts
	 * - Excludes posts that have already been shown
	 * - Filters by research category
	 * - Limits the number of posts returned
	 * 
	 * @param bool $is_featured Whether to get featured posts only
	 * @param string $categoryName The research category to filter by
	 * @param int $numberOfPosts How many posts to get
	 * @return mixed Query results from WordPress
	 */
	public function queryPosts($is_featured, $categoryName, $numberOfPosts)
	{
		$args = [
			'post_type' => 'research',
			'posts_per_page' => $numberOfPosts,
			'post__not_in' => array_map(function ($post) {
				return $post->ID;
			}, $this->allQueriedPosts),
			'orderby' => 'date',
			'order' => 'DESC',
			'tax_query' => [
				[
					'taxonomy' => 'research-categories',
					'field'    => 'slug',
					'terms'    => $categoryName,
				],
			],
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

	/**
	 * Sorts posts by their publication date
	 * 
	 * Orders posts from newest to oldest, either for:
	 * - The featured slider posts ($is_slider = true)
	 * - The additional recent posts ($is_slider = false)
	 * 
	 * @param bool $is_slider Whether sorting slider posts or recent posts
	 */
	public function sortByDate($is_slider)
	{
		if ($is_slider) {
			if ($this->recent instanceof \Timber\PostQuery) {
				$this->recent = $this->recent->to_array();
			}
			usort($this->recent, function ($a, $b) {
				return strtotime($b->post_date) - strtotime($a->post_date);
			});
		} else {
			if ($this->moreRecent instanceof \Timber\PostQuery) {
				$this->moreRecent = $this->moreRecent->to_array();
			}
			usort($this->moreRecent, function ($a, $b) {
				return strtotime($b->post_date) - strtotime($a->post_date);
			});
		}
	}

	/**
	 * Sorts the slider posts by their featured status
	 * 
	 * Reorders the featured slider posts so that posts marked as
	 * "top featured" appear first in the slider
	 */
	public function sortSliderByTopFeatured()
	{
		usort($this->recent, function ($a, $b) {
			$topFeatureA = is_array($a->top_featured_research) ? implode('', $a->top_featured_research) : $a->top_featured_research;
			$topFeatureB = is_array($b->top_featured_research) ? implode('', $b->top_featured_research) : $b->top_featured_research;
			return strcmp($topFeatureB, $topFeatureA);
		});
	}
}
