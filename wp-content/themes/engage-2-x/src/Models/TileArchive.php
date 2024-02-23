<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;
use Engage\Models\Event;

class TileArchive extends Archive
{
	public $filters = []; // when you want things organized by vertical
	
	public function __construct( $options, $query = false )
	{
		
		$defaults = [
			'filters'    => []
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
	// this is way too confusing, but seems to work fine... :/
	public function setCurrentFilter() {
		// search for the current slug.
		// If we're displaying all verticals, we'll be looking for the vertical slug as the current match.
		// if it's by postType, then we're looking for the current displayed postType
		
		// Needed to add ability to add team category info to array and filter css.
		// Might consider re-doing some of the filter stuff to acount for things like these.
		if ($this->filters['structure'] === 'vertical') {
			if (isset($this->vertical->slug)) {
				$currentSlug = $this->vertical->slug;
			} elseif (isset($this->category->slug)) {
				$currentSlug = $this->category->slug;
			}
		} else {
			$currentSlug = $this->postType->name;
		}
		
		if($this->filters['terms']) {
			foreach($this->filters['terms'] as $parentTerm) {
								if($currentSlug === $parentTerm['slug']) {
					// found the parent match!
					$this->filters['terms'][$parentTerm['slug']]['currentParent'] = true;
					
					// now see if this is just the current parent or actually the current one
					if($this->category->taxonomy === 'verticals') {
						$this->filters['terms'][$parentTerm['slug']]['current'] = true;
					} else {
						if(!empty($parentTerm['terms'])) {
							// let's find the child
							foreach($parentTerm['terms'] as $childTerm) {
								if($childTerm['slug'] === $this->category->slug) {
									$this->filters['terms'][$parentTerm['slug']]['terms'][$this->category->slug]['current'] = true;
									break;
								}
							}
						}
					}
					break;
				}
			}
		}
	}
}
