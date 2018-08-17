<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;

class TileArchive extends Archive
{
	public $filters = []; // when you want things organized by vertical

    public function __construct($options, $query = false, $class = 'Engage\Models\Article')
    {

    	$defaults = [
    		'filters'    => []
    	];

    	$options = array_merge($defaults, $options);
    	
        $this->filters = $options['filters'];

        parent::__construct($query, $class);

        // loop through the posts and if it's an event, set it as the event model instead
        foreach($this->posts as $key => $val) {
        	if($val->post_type === 'tribe_events') {
        		$this->posts[$key] = new Event($val->ID);
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
    	$currentSlug = ($this->filters['structure'] === 'vertical' ? $this->vertical->slug : $this->postType->name);

		if($this->filters['terms']) {
			foreach($this->filters['terms'] as $parentTerm) {
				if($currentSlug === $parentTerm['slug']) {
					// found the parent match!
					$this->filters['terms'][$parentTerm['slug']]['currentParent'] = true;

					// now see if this is just the current parent or actually the current one
					if($this->category->taxonomy === 'verticals') {
						$this->filters['terms'][$parentTerm['slug']]['current'] = true;
					} else {
						// let's find the child
						foreach($parentTerm['terms'] as $childTerm) {
							if($childTerm['slug'] === $this->category->slug) {
								$this->filters['terms'][$parentTerm['slug']]['terms'][$this->category->slug]['current'] = true;
								break;
							}
						}
					}

					break;
 				}
			}
		} 
    }
}


