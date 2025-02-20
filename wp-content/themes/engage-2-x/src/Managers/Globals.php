<?php
/*
* Manages object cache, post update/clearing, etc
*/

namespace Engage\Managers;

use Timber;
use Engage\Models\CategoryFilterMenu;
use Engage\Models\FilterMenu;

class Globals
{
	/**
	 * Constructor to initialize the Globals class.
	 */
	function __construct() {}

	/**
	 * Initiates the process to clear filters by setting up the necessary actions.
	 *
	 * @return void
	 */
	public function run()
	{
		$this->clearFilterMenuActions();
	}

	/**
	 * Sets up actions to clear various filter menus when certain taxonomy events occur.
	 *
	 * This method attaches WordPress actions to handle clearing the cache
	 * for various filter menus when taxonomies are edited, created, or deleted.
	 *
	 * @return void
	 */
	public function clearFilterMenuActions()
	{
		// Research menu actions
		add_action('edit_research-categories', [$this, 'clearResearchMenu'], 10, 2);
		add_action('create_research-categories', [$this, 'clearResearchMenu'], 10, 2);
		add_action('delete_research-categories', [$this, 'clearResearchMenu'], 10, 2);

		// Announcement menu actions
		add_action('edit_announcement-category', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('create_announcement-category', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('delete_announcement-category', [$this, 'clearAnnouncementMenu'], 10, 2);

		// Blog menu actions
		add_action('edit_blogs-category', [$this, 'clearBlogMenu'], 10, 2);
		add_action('create_blogs-category', [$this, 'clearBlogMenu'], 10, 2);
		add_action('delete_blogs-category', [$this, 'clearBlogMenu'], 10, 2);

		// Team menu actions
		add_action('edit_team_category', [$this, 'clearTeamMenu'], 10, 2);
		add_action('create_team_category', [$this, 'clearTeamMenu'], 10, 2);
		add_action('delete_team_category', [$this, 'clearTeamMenu'], 10, 2);

		// Event menu actions
		add_action('edit_tribe_events_cat', [$this, 'clearEventMenu'], 10, 2);
		add_action('create_tribe_events_cat', [$this, 'clearEventMenu'], 10, 2);
		add_action('delete_tribe_events_cat', [$this, 'clearEventMenu'], 10, 2);

		// On edit or publish of a post, clear everything
		add_action('save_post', [$this, 'clearMenus']);
	}

	/**
	 * Clears all relevant menus based on the post type.
	 *
	 * This method is triggered on saving a post and checks the post type to determine
	 * which menus need to be cleared. The corresponding menu for the post type
	 * will be cleared from the cache.
	 *
	 * @param int $postID The ID of the post being saved.
	 * @return void
	 */
	public function clearMenus($postID)
	{
		// If this is just a revision or it's not published, don't do anything
		if (wp_is_post_revision($postID) || get_post_status($postID) !== 'publish')
			return;

		$postType = get_post_type($postID);

		// Clear the corresponding menu based on post type
		switch ($postType) {
			case 'research':
				$this->clearResearchMenu(0, 0);
				break;
			case 'team':
				$this->clearTeamMenu(0, 0);
				break;
			case 'announcement':
				$this->clearAnnouncementMenu(0, 0);
				break;
			case 'blogs':
				$this->clearBlogMenu(0, 0);
				break;
			case 'tribe_events':
				$this->clearEventMenu(0, 0);
				break;
		}
	}

	/**
	 * Clears the cache for the announcement menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearAnnouncementMenu($term_id, $tt_id)
	{
		// delete the cache for this item
		delete_transient('announcement-filter-menu');
	}

	/**
	 * Retrieves the cached announcement menu or builds it if not cached.
	 *
	 * @return array The announcement menu.
	 */
	public function getAnnouncementMenu()
	{
		// Skip cache in development environment
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildAnnouncementMenu();
		}

		$menu = get_transient('announcement-filter-menu');
		if (!empty($menu)) {
			return $menu;
		}

		$menu = $this->buildAnnouncementMenu();
		set_transient('announcement-filter-menu', $menu);
		return $menu;
	}

	private function buildAnnouncementMenu()
	{
		$posts = Timber::get_posts([
			'post_type'      => ['announcement'],
			'posts_per_page' => -1
		]);

		$options = [
			'title'         => 'Announcements',
			'slug'          => 'announcement-menu',
			'posts'         => $posts,
			'taxonomies'    => ['announcement-category'],
			'postTypes'     => ['announcement'],
		];

		$filters = new CategoryFilterMenu($options);
		return $filters->build();
	}

	/**
	 * Clears the cache for the blog menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearBlogMenu($term_id, $tt_id)
	{
		// delete the cache for this item
		delete_transient('blogs-filter-menu');
	}

	/**
	 * Retrieves the cached blog menu or builds it if not cached.
	 *
	 * @return array The blog menu.
	 */
	public function getBlogMenu()
	{
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildBlogMenu();
		}

		$menu = get_transient('blogs-filter-menu');
		if (!empty($menu)) {
			return $menu;
		}

		$menu = $this->buildBlogMenu();
		set_transient('blogs-filter-menu', $menu);
		return $menu;
	}

	private function buildBlogMenu()
	{
		$posts = Timber::get_posts([
			'post_type'      => ['blogs'],
			'posts_per_page' => -1
		]);

		$options = [
			'title'         => 'Blogs',
			'slug'          => 'blogs-menu',
			'posts'         => $posts,
			'taxonomies'    => ['blogs-category'],
			'postTypes'     => ['blogs'],
		];

		$filters = new CategoryFilterMenu($options);
		return $filters->build();
	}

	/**
	 * Clears the cache for the event menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearEventMenu($term_id, $tt_id)
	{
		// delete the cache for this item
		delete_transient('event-filter-menu');
	}

	/**
	 * Retrieves the cached event menu or builds it if not cached.
	 *
	 * @return array The event menu.
	 */
	public function getEventMenu()
	{
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildEventMenu();
		}

		$menu = get_transient('event-filter-menu');
		if (!empty($menu)) {
			return $menu;
		}

		$menu = $this->buildEventMenu();
		set_transient('event-filter-menu', $menu);
		return $menu;
	}

	private function buildEventMenu()
	{
		$posts = Timber::get_posts([
			'post_type'      => ['tribe_events'],
			'posts_per_page' => -1
		]);

		$options = [
			'title'         => 'Events',
			'slug'          => 'event-menu',
			'posts'         => $posts,
			'taxonomies'    => ['tribe_events_cat'],
			'postTypes'     => ['tribe_events'],
			'manualLinks'   => [
				'events-by-date' => [
					'title' => 'Date',
					'slug' => 'archive-section',
					'link' => '',
					'terms' => [
						[
							'slug' => 'upcoming-events',
							'title' => 'Upcoming Events',
							'link' => site_url() . '/events/upcoming'
						],
						[
							'slug' => 'past-events',
							'title' => 'Past Events',
							'link' => site_url() . '/events/past'
						]
					]
				]
			]
		];

		$filters = new CategoryFilterMenu($options);
		return $filters->build();
	}

	/**
	 * Clears the cache for the research menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearResearchMenu($term_id, $tt_id)
	{
		// delete the cache for this item
		delete_transient('research-filter-menu');
	}

	/**
	 * Retrieves the cached research menu or builds it if not cached.
	 *
	 * @return array The research menu.
	 */
	public function getResearchMenu()
	{
		// Skip cache in development environment
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildResearchMenu();
		}

		$menu = get_transient('research-filter-menu');
		if (!empty($menu)) {
			return $menu;
		}

		$menu = $this->buildResearchMenu();
		set_transient('research-filter-menu', $menu);
		return $menu;
	}

	/**
	 * Builds the research menu without caching.
	 *
	 * @return array The research menu.
	 */
	private function buildResearchMenu()
	{
		$posts = Timber::get_posts([
			'post_type'      => ['research'],
			'posts_per_page' => -1
		]);

		$options = [
			'title'      => 'Research',
			'slug'       => 'research-menu',
			'posts'      => $posts,
			'taxonomies' => ['research-categories'],
			'postTypes'  => ['research'],
		];

		$filters = new FilterMenu($options);
		return $filters->build();
	}

	/**
	 * Clears the cache for the team menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearTeamMenu($term_id, $tt_id)
	{
		// delete the cache for this item
		delete_transient('team-filter-menu');
	}

	/**
	 * Retrieves the cached team menu or builds it if not cached.
	 *
	 * @return array The team menu.
	 */
	public function getTeamMenu()
	{
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildTeamMenu();
		}

		$menu = get_transient('team-filter-menu');
		if (!empty($menu)) {
			return $menu;
		}

		$menu = $this->buildTeamMenu();
		set_transient('team-filter-menu', $menu);
		return $menu;
	}

	private function buildTeamMenu()
	{
		$posts = Timber::get_posts([
			'post_type'      => ['team'],
			'posts_per_page' => -1
		]);

		$options = [
			'title'         => 'Team',
			'slug'          => 'team-menu',
			'posts'         => $posts,
			'taxonomies'    => ['team_category'],
			'postTypes'     => ['team'],
			'linkBase'      => 'team',
		];

		$filters = new CategoryFilterMenu($options);
		$menu = $filters->build();

		if (!empty($menu['terms'])) {
			foreach ($menu['terms'] as $key => $term) {
				if (isset($term['terms'])) {
					$temp = $term;
					unset($menu['terms'][$key]);
					$menu['terms'] = array_merge($menu['terms'], $temp['terms']);
				}
			}
		}

		return $menu;
	}

	/**
	 * Clears the cache for the board menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearBoardMenu($term_id, $tt_id)
	{
		// delete the cache for this item
		delete_transient('board-filter-menu');
	}

	/**
	 * Retrieves the cached board menu or builds it if not cached.
	 *
	 * @return array The board menu.
	 */
	public function getBoardMenu()
	{
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildBoardMenu();
		}

		$menu = get_transient('board-filter-menu');
		if (!empty($menu)) {
			return $menu;
		}

		$menu = $this->buildBoardMenu();
		set_transient('board-filter-menu', $menu);
		return $menu;
	}

	private function buildBoardMenu()
	{
		$posts = Timber::get_posts([
			'post_type'      => ['board'],
			'posts_per_page' => -1
		]);

		$options = [
			'title'         => 'Board',
			'slug'          => 'board-menu',
			'posts'         => $posts,
			'taxonomies'    => ['team_category'],
			'postTypes'     => ['board'],
		];

		$filters = new CategoryFilterMenu($options);
		return $filters->build();
	}

	/**
	 * Retrieves the cached research category menu or builds it if not cached.
	 * This function manages caching and returns a menu structure for a specific research category.
	 *
	 * @param string $category The slug of the research category.
	 * @return array The research category menu.
	 */
	public function getResearchCategoryMenu($category) {
		// In development mode, skip the cache to always get fresh data
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
			return $this->buildResearchCategoryMenu($category);
		}

		// Try to get the menu from cache first
		// Each category has its own cache with a unique key based on the category slug
		$menu = get_transient('research-category-filter-menu--' . $category);
		if(!empty($menu)) {
			return $menu; // Return cached menu if it exists
		}
		
		// If no cached menu exists, build a new one
		$menu = $this->buildResearchCategoryMenu($category);
		// Store the new menu in cache for future requests
		set_transient('research-category-filter-menu--' . $category, $menu);
		return $menu;
	}

	/**
	 * Builds a filter menu for a specific research category.
	 * This creates a menu structure showing all content (research and blogs)
	 * that belongs to the specified research category.
	 *
	 * @param string $categorySlug The slug of the research category to build menu for
	 * @return array The built menu structure
	 */
	private function buildResearchCategoryMenu($categorySlug) {
		// Get the full term object for the category
		$category = get_term_by('slug', $categorySlug, 'research-categories');
		
		// Define which post types to include in this menu
		// Currently only including research and blogs as they share categories
		$postTypes = ['research', 'blogs'];
		
		// Get all posts from specified post types that have this category
		$posts = Timber::get_posts([
			'post_type'      => $postTypes,
			'tax_query'      => [
				[
					'taxonomy' => 'research-categories', // Filter by research categories
					'field'    => 'slug',               // Use the slug to identify the category
					'terms'    => $category->slug       // The specific category we want
				]
			],
			'posts_per_page' => -1  // Get all matching posts
		]);
		
		// Set up the options for building the filter menu
		$options = [
			'title'         => $category->name,         // Use category name as menu title
			'slug'          => $category->slug . '-menu', // Create unique menu slug
			'posts'         => $posts,                  // All posts that belong to this category
			'taxonomies'    => [
				'research-categories',                  // Include research categories
				'blogs-category'                        // And blog categories
			],
			'postTypes'     => $postTypes              // The post types we're including
		];

		// Create and return the filter menu
		$filters = new FilterMenu($options);
		return $filters->build();
	}
}
