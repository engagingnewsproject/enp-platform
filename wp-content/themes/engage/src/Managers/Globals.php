<?php
/*
 * Manages object cache, post update/clearing, etc
 */
namespace Engage\Managers;

use Timber;

class Globals {

	function __construct() {

		// clear research category menu
        add_action('edit_research-categories', [$this, 'clearResearchMenu'], 10, 2);
        add_action('create_research-categories', [$this, 'clearResearchMenu'], 10, 2);
        add_action('delete_research-categories', [$this, 'clearResearchMenu'], 10, 2);
        add_action('edit_verticals', [$this, 'clearResearchMenu'], 10, 2);
        add_action('create_verticals', [$this, 'clearResearchMenu'], 10, 2);
        add_action('delete_verticals', [$this, 'clearResearchMenu'], 10, 2);
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
  		$filters = wp_cache_get('research-filter-menu');
  		if(!empty($filters)) {
  			return $filters;
  		}

  		$posts = new Timber\PostQuery([
  			'post_type'      => ['research'],
  			'posts_per_page' => -1
  		]);

  		$options = [
  			'posts' 			=> $posts,
  			'taxonomies'		=> [ 'vertical', 'research-categories' ],
			'taxonomyStructure' => 'vertical',
			'postTypes'			=> [ 'research' ],
			'rootURL'			=> get_post_type_archive_link('research')
  		];

  		// we don't have the research menu, so build it
  		$filters = new \Engage\Models\FilterMenu($options);
  		$menu = $filters->build();

  		wp_cache_set('research-filter-menu', $menu );

  		return $menu;
  	}
	
}