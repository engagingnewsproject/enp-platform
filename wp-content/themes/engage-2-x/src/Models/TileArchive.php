<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;
use Engage\Models\Event;

class TileArchive extends Archive
{
	/** @var array Filter settings for organizing content */
	public $filters = [];
	
	/** @var WP_Query WordPress query object */
	protected $query;
	
	/**
	 * Initialize the archive with filters and posts
	 * 
	 * @param array $options Configuration options including filter structure
	 * @param WP_Query|false $query Optional WordPress query object
	 */
	public function __construct($options, $query = false)
	{
		// Set default filter structure to prevent undefined index errors
		$defaults = [
			'filters' => [
				'structure' => 'default', // Default structure if none provided
				'terms' => []            // Terms for filter menu
			]
		];
		
		$options = array_merge($defaults, $options);
		$this->filters = $options['filters'];
		$this->query = $query;
		
		parent::init($query);
		
		// loop through the posts and if it's an event, set it as the event model instead
		foreach($this->posts as $key => $val) {
			if($val->post_type === 'tribe_events') {
				// $this->posts[$key] = new Event($val->ID); // TODO: get events done.
			}
		}
		
		// This is usually already set from a global. If it's empty, then there's no sidebar
		if(!empty($this->filters)) {
			// get the current filter menu item
			$this->setCurrentFilter();
		}
	}
	
	/**
	 * Set the current filter based on the archive type
	 * Handles multiple archive types:
	 * - Post type archives
	 * - Category archives
	 * - Research category archives (with primary/subcategory structure)
	 */
	public function setCurrentFilter() {
		// Initialize currentSlug to prevent undefined variable
		$currentSlug = '';
		
		// Determine the current slug based on archive type
		// First check if structure key exists and matches postTypes
		if (isset($this->filters['structure']) && $this->filters['structure'] === 'postTypes') {
			// For post type archives (e.g., /research/)
			$currentSlug = $this->postType->name;
		} elseif (is_object($this->category) && isset($this->category->slug)) {
			// For regular category archives
			$currentSlug = $this->category->slug;
		} elseif (isset($this->query) && isset($this->query->query['research-categories'])) {
			// For research category archives (e.g., /media-ethics/research/category/case-studies/)
			// Get the primary category (former vertical) from the URL
			$currentSlug = explode(',', $this->query->query['research-categories'])[0];
		}
		
		// Process the filter terms if they exist
		if($this->filters['terms']) {
			foreach($this->filters['terms'] as $parentTerm) {
				if($currentSlug === $parentTerm['slug']) {
					// Mark the parent term as current when matched
					$this->filters['terms'][$parentTerm['slug']]['currentParent'] = true;
					
					// Check for child terms (subcategories)
					if(!empty($parentTerm['terms'])) {
						// Find matching child term
						foreach($parentTerm['terms'] as $childTerm) {
							if(
								// Check both regular category and research subcategory
								(is_object($this->category) && isset($this->category->slug) && $childTerm['slug'] === $this->category->slug) ||
								(isset($this->query) && isset($this->query->query['subcategory']) && $childTerm['slug'] === $this->query->query['subcategory'])
							) {
								// Mark the child term as current when matched
								$this->filters['terms'][$parentTerm['slug']]['terms'][$childTerm['slug']]['current'] = true;
								break;
							}
						}
					} else {
						// If no child terms, mark the parent as current
						$this->filters['terms'][$parentTerm['slug']]['current'] = true;
					}
					break;
				}
			}
		}
	}
}
