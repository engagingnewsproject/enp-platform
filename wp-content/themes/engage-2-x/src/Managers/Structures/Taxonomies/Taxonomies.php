<?php
/*
 * Registers taxonomies
 */

namespace Engage\Managers\Structures\Taxonomies;

class Taxonomies
{
	// An array to store the taxonomies that need to be registered
	protected $taxonomies = [];

	/**
	 * Constructor to initialize the class with a list of taxonomies.
	 *
	 * @param array $taxonomies An array of taxonomy method names to be registered.
	 */
	public function __construct($taxonomies)
	{
		$this->taxonomies = $taxonomies;
	}

	/**
	 * Method to run the registration process for each taxonomy.
	 *
	 * Iterates over the list of taxonomies provided during class instantiation
	 * and calls each taxonomy's registration method.
	 */
	public function run()
	{
		foreach ($this->taxonomies as $taxonomy) {
			$this->$taxonomy();
		}
	}

	/**
	 * Method to register the 'Verticals' taxonomy.
	 *
	 * This method defines the 'Verticals' taxonomy, including its labels, 
	 * settings, and the post types it applies to. The taxonomy is hidden from
	 * the WordPress admin interface but remains functional in the backend.
	 */
	public function Verticals()
	{
		// Labels for the 'Verticals' taxonomy
		$labels = array(
			'name'              => _x('Verticals', 'taxonomy general name'),
			'singular_name'     => _x('Vertical', 'taxonomy singular name'),
			'search_items'      => __('Search Verticals'),
			'all_items'         => __('All Verticals'),
			'parent_item'       => __('Parent Vertical'),
			'parent_item_colon' => __('Parent Vertical:'),
			'edit_item'         => __('Edit Vertical'),
			'update_item'       => __('Update Vertical'),
			'add_new_item'      => __('Add New Vertical'),
			'new_item_name'     => __('New Vertical Name'),
			'menu_name'         => __('Verticals'),
		);

		// Arguments for the 'Verticals' taxonomy
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true, // Hide from admin UI
			'show_admin_column' => true, // Hide from admin columns
			// we do not want to show this in the admin menu on the dev, staging, or production sites. only on local
			'show_in_menu'      => \ENV_LOCAL, // Hide from admin menu
			'query_var'         => true,
			'has_archive'       => true,
			'rewrite'           => ['slug' => 'vertical'],
		);

		// Post types to which the 'Verticals' taxonomy will be registered
		$postTypes = [
			'post',
			'page',
			'research',
			'funders',
			'announcement',
			'blogs',
			'tribe_events',
			'board'
		];

		// Register the 'Verticals' taxonomy for the specified post types with the provided arguments
		register_taxonomy('verticals', $postTypes, $args);
	}
}
