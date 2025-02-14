<?php
/*
* Manages object cache, post update/clearing, etc
*/
namespace Engage\Managers;

use Timber;
use Engage\Models\CategoryFilterMenu;
use Engage\Models\FilterMenu;

class Globals {
	/**
	 * Constructor to initialize the Globals class.
	 */
	function __construct() {}
	
	/**
	 * Initiates the process to clear filters by setting up the necessary actions.
	 *
	 * @return void
	 */
	public function run() {
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
	public function clearFilterMenuActions() {
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
	public function clearMenus($postID) {
		// If this is just a revision or it's not published, don't do anything
		if ( wp_is_post_revision( $postID ) || get_post_status($postID) !== 'publish')
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
	public function clearAnnouncementMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('announcement-filter-menu');
	}
	
	/**
	 * Retrieves the cached announcement menu or builds it if not cached.
	 *
	 * @return array The announcement menu.
	 */
	public function getAnnouncementMenu() {
		$menu = get_transient('announcement-filter-menu');
		if(!empty($menu)) {
			return $menu;
		}
		
		$posts = Timber::get_posts([
			'post_type'      => ['announcement'],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'				=> 'Announcements',
			'slug'				=> 'announcement-menu',
			'posts' 			=> $posts,
			'taxonomies'		=> [ 'announcement-category' ],
			'postTypes'			=> [ 'announcement' ],
		];
		
		// we don't have the announcement menu, so build it
		$filters = new CategoryFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('announcement-filter-menu', $menu );
		
		return $menu;
	}
	
	/**
	 * Clears the cache for the blog menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearBlogMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('blogs-filter-menu');
	}
	
	/**
	 * Retrieves the cached blog menu or builds it if not cached.
	 *
	 * @return array The blog menu.
	 */
	public function getBlogMenu() {
		$menu = get_transient('blogs-filter-menu');
		if(!empty($menu)) {
			return $menu;
		}
		
		$posts = Timber::get_posts([
			'post_type'      => ['blogs'],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'				=> 'Blogs',
			'slug'				=> 'blogs-menu',
			'posts' 			=> $posts,
			'taxonomies'		=> [ 'blogs-category' ],
			'postTypes'			=> [ 'blogs' ],
		];
		
		// we don't have the blogs menu, so build it
		$filters = new CategoryFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('blogs-filter-menu', $menu );
		
		return $menu;
	}
	
	/**
	 * Clears the cache for the event menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearEventMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('event-filter-menu');
	}
	
	/**
	 * Retrieves the cached event menu or builds it if not cached.
	 *
	 * @return array The event menu.
	 */
	public function getEventMenu() {
		$menu = get_transient('event-filter-menu');
		if(!empty($menu)) {
			return $menu;
		}
		
		$posts = Timber::get_posts([
			'post_type'      => ['tribe_events'],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'				=> 'Events',
			'slug'				=> 'event-menu',
			'posts' 			=> $posts,
			'taxonomies'		=> [ 'tribe_events_cat' ],
			'postTypes'			=> [ 'tribe_events' ],
			'manualLinks' 		=> [
				'events-by-date' => [
					'title' => 'Date',
					'slug' => 'archive-section',
					'link' => '',
					'terms' => [
						[
							'slug' => 'upcoming-events',
							'title' => 'Upcoming Events',
							'link' => site_url().'/events/upcoming'
						],
						[
							'slug' => 'past-events',
							'title' => 'Past Events',
							'link' => site_url().'/events/past'
						]
					]
				]
			]
		];
	
		// we don't have the event menu, so build it
		$filters = new CategoryFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('event-filter-menu', $menu );
		
		return $menu;
	}

	/**
	 * Clears the cache for the research menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearResearchMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('research-filter-menu');
	}

	/**
	 * Retrieves the cached research menu or builds it if not cached.
	 *
	 * @return array The research menu.
	 */
	public function getResearchMenu() {
		$menu = get_transient('research-filter-menu');
		if(!empty($menu)) {
			return $menu;
		}
		
		$posts = Timber::get_posts([
			'post_type'      => ['research'],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'      => 'Research',
			'slug'       => 'research-menu',
			'posts'      => $posts,
			'taxonomies' => ['research-categories'],  // Remove verticals
			'postTypes'  => ['research'],
		];
		
		// Use regular FilterMenu instead of CategoryFilterMenu
		$filters = new FilterMenu($options);
		$menu = $filters->build();
		
		set_transient('research-filter-menu', $menu );
		
		return $menu;
	}

	/**
	 * Clears the cache for the team menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearTeamMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('team-filter-menu');
	}

	/**
	 * Retrieves the cached team menu or builds it if not cached.
	 *
	 * @return array The team menu.
	 */
	public function getTeamMenu() {
		$menu = get_transient('team-filter-menu');
		// retrieves the updated menu
		if(!empty($menu)) {
			return $menu;
		}
		
		$posts = Timber::get_posts([
			'post_type'      => ['team'],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'				=> 'Team',
			'slug'				=> 'team-menu',
			'posts' 			=> $posts,
			'taxonomies'		=> [ 'team_category' ],
			'postTypes'			=> [ 'team' ],
			'linkBase'			=> 'team',
		];
		
		// we don't have the team menu, so build it
		$filters = new CategoryFilterMenu($options);
		$menu = $filters->build();
		
		if(!empty($menu['terms'])) {
			foreach($menu['terms'] as $key => $term) {
				if(isset($term['terms'])) {
					$temp = $term;
					unset($menu['terms'][$key]);
					$menu['terms'] = array_merge($menu['terms'], $temp['terms']);
				}
			}
		}
		
		set_transient('team-filter-menu', $menu );
		
		return $menu;
	}

	/**
	 * Clears the cache for the board menu.
	 *
	 * @param int $term_id Term ID that triggered the cache clear.
	 * @param int $tt_id Term Taxonomy ID that triggered the cache clear.
	 * @return void
	 */
	public function clearBoardMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('board-filter-menu');
	}

	/**
	 * Retrieves the cached board menu or builds it if not cached.
	 *
	 * @return array The board menu.
	 */
	public function getBoardMenu() {
		$menu = get_transient('board-filter-menu');
		if(!empty($menu)) {
			return $menu;
		}
		
		$posts = Timber::get_posts([
			'post_type'      => ['board'],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'				=> 'Board',
			'slug'				=> 'board-menu',
			'posts' 			=> $posts,
			'taxonomies'		=> [ 'team_category' ],
			'postTypes'			=> [ 'board' ],
		];
		
		// we don't have the team menu, so build it
		$filters = new CategoryFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('board-filter-menu', $menu );
		
		return $menu;
	}
}
