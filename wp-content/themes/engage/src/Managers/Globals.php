<?php
/*
 * Manages object cache, post update/clearing, etc
 */
namespace Engage\Managers;

use Timber;

class Globals {

	function __construct() {

		$this->clearFilterMenuActions();
	}

	public function clearFilterMenuActions() {
		// clear research category menu
        add_action('edit_research-categories', [$this, 'clearResearchMenu'], 10, 2);
        add_action('create_research-categories', [$this, 'clearResearchMenu'], 10, 2);
        add_action('delete_research-categories', [$this, 'clearResearchMenu'], 10, 2);
        add_action('edit_verticals', [$this, 'clearResearchMenu'], 10, 2);
        add_action('create_verticals', [$this, 'clearResearchMenu'], 10, 2);
        add_action('delete_verticals', [$this, 'clearResearchMenu'], 10, 2);

        // clear team category menu
        add_action('edit_team_category', [$this, 'clearTeamMenu'], 10, 2);
        add_action('create_team_category', [$this, 'clearTeamMenu'], 10, 2);
        add_action('delete_team_category', [$this, 'clearTeamMenu'], 10, 2);
        add_action('edit_verticals', [$this, 'clearTeamMenu'], 10, 2);
        add_action('create_verticals', [$this, 'clearTeamMenu'], 10, 2);
        add_action('delete_verticals', [$this, 'clearTeamMenu'], 10, 2);

        // clear vertical landing page menu
        add_action('edit_verticals', [$this, 'clearVerticalMenu'], 10, 2);
        add_action('create_verticals', [$this, 'clearVerticalMenu'], 10, 2);
        add_action('delete_verticals', [$this, 'clearVerticalMenu'], 10, 2);

        // on edit or publish of a post, clear evertyhing
        add_action('save_post', [$this, 'clearMenus']);
	}

	public function clearMenus($postID) {
		// If this is just a revision or it's not published, don't do anything
		if ( wp_is_post_revision( $postID ) || get_post_status($postID) !== 'published')
			return;


		$postType = get_post_type($postID);

		if($postType === 'research') {
			$this->clearResearchMenu();
		} else if($postType === 'team') {
			$this->clearTeamMenu();
		} else {
			// find out which, if any verticals it has
			$verticals = wp_get_post_terms( $postID, 'verticals' );
			if($verticals) {
				foreach($verticals as $vertical) {
					$this->clearVerticalMenu($vertical->term_id, 'verticals');
				}
			}
		}
	}

	
	/**
     * Clear the cache for the research menu
     *
     */
    public function clearResearchMenu($term_id, $taxonomy) {
        // delete the cache for this item
        wp_cache_delete('research-filter-menu');
    }

  	public function getResearchMenu() {
  		$menu = wp_cache_get('research-filter-menu');
  		if(!empty($menu)) {
  			return $menu;
  		}

  		$posts = new Timber\PostQuery([
  			'post_type'      => ['research'],
  			'posts_per_page' => -1
  		]);

  		$options = [
  			'title'				=> 'Research',
  			'slug'				=> 'research-menu',
  			'posts' 			=> $posts,
  			'taxonomies'		=> [ 'vertical', 'research-categories' ],
			'taxonomyStructure' => 'vertical',
			'postTypes'			=> [ 'research' ],
  		];

  		// we don't have the research menu, so build it
  		$filters = new \Engage\Models\VerticalsFilterMenu($options);
  		$menu = $filters->build();

  		wp_cache_set('research-filter-menu', $menu );

  		return $menu;
  	}


  	/**
     * Clear the cache for the team menu
     *
     */
    public function clearTeamMenu($term_id, $taxonomy) {
        // delete the cache for this item
        wp_cache_delete('team-filter-menu');
    }

  	public function getTeamMenu() {
  		$menu = wp_cache_get('team-filter-menu');
  		if(!empty($menu)) {
  			return $menu;
  		}

  		$posts = new Timber\PostQuery([
  			'post_type'      => ['team'],
  			'posts_per_page' => -1
  		]);

  		$options = [
  			'title'				=> 'Team',
  			'slug'				=> 'team-menu',
  			'posts' 			=> $posts,
  			'taxonomies'		=> [ 'vertical', 'team_category' ],
			'taxonomyStructure' => 'vertical',
			'postTypes'			=> [ 'team' ],
  		];

  		// we don't have the team menu, so build it
  		$filters = new \Engage\Models\VerticalsFilterMenuu($options);
  		$menu = $filters->build();

  		wp_cache_set('team-filter-menu', $menu );

  		return $menu;
  	}


  	/**
     * Clear the cache for the vertical menu
     *
     */
    public function clearVerticalhMenu($term_id, $taxonomy) {
    	$term = get_term_by('id', $term_id, $taxonomy);
        // delete the cache for this item
        wp_cache_delete('vertical-filter-menu--'.$term->slug);
    }

  	public function getVerticalMenu($vertical) {
  		$menu = wp_cache_get('vertical-filter-menu--'.$vertical);
  		if(!empty($menu)) {
  			return $menu;
  		}

  		$vertical = get_term_by('slug', $vertical, 'verticals');

  		$postTypes = [ 'research', 'team', 'post' ];

  		$posts = new Timber\PostQuery([
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
  			'taxonomies'		=> ['research-categories', 'team_category', 'category'],
			'taxonomyStructure' => 'sections',
			'postTypes'			=> $postTypes
  		];

  		// we don't have the vertical menu, so build it
  		$filters = new \Engage\Models\FilterMenu($options);
  		$menu = $filters->build();

  		wp_cache_set('vertical-filter-menu--'.$vertical->slug, $menu );

  		return $menu;
  	}

  	//
	
}