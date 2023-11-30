<?php
/*
* Manages object cache, post update/clearing, etc
*/
namespace Engage\Managers;

use Timber;
use Engage\Models\VerticalsFilterMenu;
use Engage\Models\FilterMenu;

class Globals {
	
	function __construct() {
		
	}
	
	/**
	 * Run clear filters.
	 *
	 * @return void
	 */
	public function run() {
		$this->clearFilterMenuActions();
	}
	
	/**
	 * Clear research category menu
	 *
	 * @return void
	 */
	public function clearFilterMenuActions() {
		// Add actions to clear various filter menus when certain taxonomy events occur.
		add_action('edit_research-categories', [$this, 'clearResearchMenu'], 10, 2);
		add_action('create_research-categories', [$this, 'clearResearchMenu'], 10, 2);
		add_action('delete_research-categories', [$this, 'clearResearchMenu'], 10, 2);
		
		// Similar actions for announcement, blogs, team, event, and vertical menus.
		add_action('edit_verticals', [$this, 'clearResearchMenu'], 10, 2);
		add_action('create_verticals', [$this, 'clearResearchMenu'], 10, 2);
		add_action('delete_verticals', [$this, 'clearResearchMenu'], 10, 2);
		
		// clear announcement filter menu
		add_action('edit_announcement-category', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('create_announcement-category', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('delete_announcement-category', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('edit_verticals', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('create_verticals', [$this, 'clearAnnouncementMenu'], 10, 2);
		add_action('delete_verticals', [$this, 'clearAnnouncementMenu'], 10, 2);
		
		// clear blogs filter menu
		add_action('edit_blogs-category', [$this, 'clearBlogMenu'], 10, 2);
		add_action('create_blogs-category', [$this, 'clearBlogMenu'], 10, 2);
		add_action('delete_blogs-category', [$this, 'clearBlogMenu'], 10, 2);
		add_action('edit_verticals', [$this, 'clearBlogMenu'], 10, 2);
		add_action('create_verticals', [$this, 'clearBlogMenu'], 10, 2);
		add_action('delete_verticals', [$this, 'clearBlogMenu'], 10, 2);
		
		// clear team category menu
		add_action('edit_team_category', [$this, 'clearTeamMenu'], 10, 2);
		add_action('create_team_category', [$this, 'clearTeamMenu'], 10, 2);
		add_action('delete_team_category', [$this, 'clearTeamMenu'], 10, 2);
		add_action('edit_verticals', [$this, 'clearTeamMenu'], 10, 2);
		add_action('create_verticals', [$this, 'clearTeamMenu'], 10, 2);
		add_action('delete_verticals', [$this, 'clearTeamMenu'], 10, 2);
		
		// clear event menu
		add_action('edit_tribe_events_cat', [$this, 'clearEventMenu'], 10, 2);
		add_action('create_tribe_events_cat', [$this, 'clearEventMenu'], 10, 2);
		add_action('delete_tribe_events_cat', [$this, 'clearEventMenu'], 10, 2);
		add_action('edit_verticals', [$this, 'clearEventMenu'], 10, 2);
		add_action('create_verticals', [$this, 'clearEventMenu'], 10, 2);
		add_action('delete_verticals', [$this, 'clearEventMenu'], 10, 2);
		
		// clear vertical landing page menu
		add_action('edit_verticals', [$this, 'clearVerticalMenu'], 10, 2);
		add_action('create_verticals', [$this, 'clearVerticalMenu'], 10, 2);
		add_action('delete_verticals', [$this, 'clearVerticalMenu'], 10, 2);
		
		// On edit or publish of a post, clear everything.
		add_action('save_post', [$this, 'clearMenus']);
	}
	
	/**
	 * Clear menus
	 *
	 * @param [type] $postID
	 * @return void
	 */
	public function clearMenus($postID) {
		// If this is just a revision or it's not published, don't do anything
		if ( wp_is_post_revision( $postID ) || get_post_status($postID) !== 'publish')
		return;
		
		$postType = get_post_type($postID);
		
		// Depending on the post type, clear the corresponding menu.
		if($postType === 'research') {
			$this->clearResearchMenu(0, 0);
		}
		else if($postType === 'team') {
			$this->clearTeamMenu(0, 0);
		}
		else if($postType === 'announcement') {
			$this->clearAnnouncementMenu(0, 0);
		}
		else if($postType === 'blogs') {
			$this->clearBlogMenu(0, 0);
		}
		else if($postType === 'tribe_events') {
			$this->clearEventMenu(0, 0);
		}
		
		// Always clear the vertical menus.
		// Find out which, if any, verticals it has and clear the corresponding menu.
		$verticals = wp_get_post_terms( $postID, 'verticals' );
		if($verticals) {
			foreach($verticals as $vertical) {
				$this->clearVerticalMenu($vertical->term_id, 'verticals');
			}
		}
	}
	
	/**
	 * Clear the cache for the annoucnement menu
	 *
	 * @param [type] $term_id
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearAnnouncementMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('announcement-filter-menu');
	}
	
	/**
	 * Get the announcement menu.
	 *
	 * @return void
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
			'taxonomies'		=> [ 'vertical', 'announcement-category' ],
			'postTypes'			=> [ 'announcement' ],
		];
		
		// we don't have the announcement menu, so build it
		$filters = new VerticalsFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('announcement-filter-menu', $menu );
		
		return $menu;
	}
	
	/**
	 * Clear the cache for the blogs menu
	 *
	 * @param [type] $term_id
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearBlogMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('blogs-filter-menu');
	}
	
	/**
	 * Get the blog menu
	 *
	 * @return void
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
			'taxonomies'		=> [ 'vertical', 'blogs-category' ],
			'postTypes'			=> [ 'blogs' ],
		];
		
		// we don't have the blogs menu, so build it
		$filters = new VerticalsFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('blogs-filter-menu', $menu );
		
		return $menu;
	}
	
	/**
	 * Clear the cache for the event menu
	 *
	 * @param [type] $term_id
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearEventMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('event-filter-menu');
	}
	
	/**
	 * Get the event menu.
	 *
	 * @return void
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
			'taxonomies'		=> [ 'vertical', 'tribe_events_cat' ],
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
		$filters = new VerticalsFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('event-filter-menu', $menu );
		
		return $menu;
	}

	/**
	 * Clear the cache for the research menu
	 *
	 * @param [type] $term_id
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearResearchMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('research-filter-menu');
	}

	/**
	 * Get the research menu.
	 *
	 * @return void
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
			'title'				=> 'Research',
			'slug'				=> 'research-menu',
			'posts' 			=> $posts,
			'taxonomies'	=> [ 'vertical', 'research-categories' ],
			'postTypes'		=> [ 'research' ],
		];
		
		// we don't have the research menu, so build it
		$filters = new VerticalsFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('research-filter-menu', $menu );
		
		return $menu;
	}

	/**
	 * Clear the cache for the team menu
	 *
	 * @param [type] $term_id
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearTeamMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('team-filter-menu');
	}

	/**
	 * Get the team menu
	 *
	 * @return void
	 */
	public function getTeamMenu() {
		$menu = get_transient('team-filter-menu');
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
			'taxonomies'		=> [ 'vertical', 'team_category' ],
			'postTypes'			=> [ 'team' ],
			'linkBase'			=> 'team',
		];
		
		// we don't have the team menu, so build it
		$filters = new VerticalsFilterMenu($options);
		$menu = $filters->build();
		
		if(!empty($menu['terms'])) {
			foreach($menu['terms'] as $key => $term) {
				// unset the terms array of the terms if it's a vertical
				if($term['taxonomy'] === 'verticals') {
					unset($menu['terms'][$key]['terms']);
				}
				else {
					// moves team categories out to the main['terms'] array that way they are
					// more or less treated like verticals on the display.
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
	 * Clear the board menu.
	 *
	 * @param [type] $term_id
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearBoardMenu($term_id, $tt_id) {
		// delete the cache for this item
		delete_transient('board-filter-menu');
	}

	/**
	 * Same as team member stuff, but with board members instead
	 *
	 * @return void
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
			'taxonomies'		=> [ 'vertical', 'team_category' ],
			'postTypes'			=> [ 'board' ],
		];
		
		// we don't have the team menu, so build it
		$filters = new VerticalsFilterMenu($options);
		$menu = $filters->build();
		
		set_transient('board-filter-menu', $menu );
		
		return $menu;
	}


	/**
	 * Clear the cache for the vertical menu
	 *
	 * @param [type] $termID
	 * @param [type] $tt_id
	 * @return void
	 */
	public function clearVerticalMenu($termID, $tt_id) {
		$term = get_term($termID);
		// delete the cache for this item
		delete_transient('vertical-filter-menu--'.$term->slug);
	}

	/**
	 * Get the vertical menu
	 *
	 * @param [type] $vertical
	 * @return void
	 */
	public function getVerticalMenu($vertical) {
		$menu = get_transient('vertical-filter-menu--'.$vertical);
		if(!empty($menu)) {
			return $menu;
		}
		
		$vertical = get_term_by('slug', $vertical, 'verticals');
		
		// The filter menu will be built in this order
		$postTypes = [ 'research',  'blogs', 'announcement', 'tribe_events', 'post',  'team' ];
		
		$posts = Timber::get_posts([
		'post_type'      => $postTypes,
		'tax_query'		=> [
			[
				'taxonomy' => 'verticals',
				'field'	=> 'slug',
				'terms'	=> $vertical->slug
				]
			],
			'posts_per_page' => -1
		]);
		
		$options = [
			'title'				=> $vertical->name,
			'slug'				=> $vertical->slug.'-menu',
			'posts' 			=> $posts,
			'taxonomies'		=> ['research-categories', 'blogs-category', 'announcement-category', 'tribe_events_cat', 'category', 'team_category'],
			'postTypes'			=> $postTypes
		];

		// we don't have the vertical menu, so build it
		$filters = new FilterMenu($options);
		$menu = $filters->build();
		
		set_transient('vertical-filter-menu--'.$vertical->slug, $menu );
		
		return $menu;
	}
	
}
