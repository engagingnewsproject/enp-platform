<?php

namespace Engage\Models;

use Timber;

/**
 * Homepage Model
 * 
 * Handles content organization for the front page, including:
 * - Featured slider posts (managed via ACF relationship field 'slider_posts')
 * - "More Research" grid (8 most recent research posts, excluding slider posts)
 * - Funders display for footer
 * 
 * @package Engage\Models
 */
class Homepage
{
	/** @var array Recent research posts for "More Research" grid section */
	public $moreRecent;

	/** @var array Funders for footer display */
	public $funders;

	/**
	 * Initialize homepage content
	 * Sets up funders and recent research posts
	 */
	public function __construct()
	{
		$this->setFunders();
		$this->setMoreRecent();
	}

	/**
	 * Get recent research posts for the "More Research" grid
	 * 
	 * Process:
	 * 1. Gets slider posts from front page ACF field
	 * 2. Extracts their IDs to exclude from query
	 * 3. Queries 8 most recent research posts, excluding slider posts
	 * 
	 * @uses ACF get_field() to get slider posts
	 * @uses Timber::get_posts() for querying research posts
	 */
	public function setMoreRecent()
	{
		$exclude_ids = $this->getSliderPostIds();

		$this->moreRecent = \Timber::get_posts([
			'post_type' => 'research',
			'posts_per_page' => 8,
			'post__not_in' => $exclude_ids,
			'orderby' => 'date',
			'order' => 'DESC',
			'post_status' => 'publish'
		]);
	}

	/**
	 * Get IDs of posts selected in the slider
	 * 
	 * @return array Array of post IDs to exclude
	 */
	private function getSliderPostIds()
	{
		$front_page_id = get_option('page_on_front');
		$slider_posts = get_field('slider_posts', $front_page_id);

		$exclude_ids = [];
		if (!empty($slider_posts)) {
			foreach ($slider_posts as $post) {
				if (is_object($post)) {
					$exclude_ids[] = $post->ID;
				} elseif (is_array($post)) {
					$exclude_ids[] = $post['ID'];
				} else {
					$exclude_ids[] = $post;
				}
			}
		}

		return $exclude_ids;
	}

	/**
	 * Get funders for footer display
	 * Orders funders by menu_order field
	 * 
	 * @uses Timber::get_posts() for querying funder posts
	 */
	public function setFunders()
	{
		$this->funders = \Timber::get_posts([
			'post_type' => 'funders',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		]);
	}
}
