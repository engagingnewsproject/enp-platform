<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;
use Engage\Models\Event;

class TileArchive extends Archive
{
	public $filters = []; // Filter settings for organizing content
	
	public function __construct($options, $query = false)
	{
		$defaults = [
			'filters' => []
		];
		
		$options = array_merge($defaults, $options);
		$this->filters = $options['filters'];
		
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
	
	// set the current filter based on the archive
	public function setCurrentFilter() {
		// search for the current slug in post type or category
		if ($this->filters['structure'] === 'postTypes') {
			$currentSlug = $this->postType->name;
		} elseif (isset($this->category->slug)) {
			$currentSlug = $this->category->slug;
		}
		
		if($this->filters['terms']) {
			foreach($this->filters['terms'] as $parentTerm) {
				if($currentSlug === $parentTerm['slug']) {
					// found the parent match!
					$this->filters['terms'][$parentTerm['slug']]['currentParent'] = true;
					
					// Check if this is a category-based filter
					if(!empty($parentTerm['terms'])) {
						// let's find the child
						foreach($parentTerm['terms'] as $childTerm) {
							if($childTerm['slug'] === $this->category->slug) {
								$this->filters['terms'][$parentTerm['slug']]['terms'][$this->category->slug]['current'] = true;
								break;
							}
						}
					} else {
						$this->filters['terms'][$parentTerm['slug']]['current'] = true;
					}
					break;
				}
			}
		}
	}
}
