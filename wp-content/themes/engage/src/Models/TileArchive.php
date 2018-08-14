<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;

class TileArchive extends Archive
{
	public $filters = []; // when you want things organized by vertical

    public function __construct($options, $query = false)
    {

    	$defaults = [
    		'taxonomies' => [],
    		'taxonomyStructure'  => ( get_query_var('taxonomy_structure') ? get_query_var('taxonomy_structure') : 'sections'),
    		'postTypes'  => [],
    		'filters'    => []
    	];

    	$options = array_merge($defaults, $options);
    	
        $this->filters = $options['filters'];

        parent::__construct($query);

        // This is usually already set from a global
        if(empty($this->filters)) {
	    	$this->setFilters($options);
	    }

	    // get the current filter menu item
	    $this->setCurrentFilter();
    }

    public function setfilters() {

    	$options = [
        	'taxonomies' => $options['taxonomies'],
            'taxonomyStructure'  => $options['taxonomyStructure'],
            'postTypes'  => $options['postTypes'],
            'posts'	     => $this->posts
        ];
    	$filters = new FilterMenu($options);

        $this->filters = $filters->build();
    }


    // set the current filter based on the archive
    // this is way too confusing, but seems to work fine... :/
    public function setCurrentFilter() {
    	//var_dump($this->filters);
    	// search for the term
		if($this->filters['categories']['structure'] === 'vertical') {
			foreach($this->filters['categories']['terms'] as $verticalTerm) {
				if($this->vertical->slug === $verticalTerm['slug']) {
					$this->filters['categories']['terms'][$verticalTerm['slug']]['currentParent'] = true;

					// now see if the vertical is the current parent or actually the current one
					if($this->category->taxonomy === 'verticals') {
						$this->filters['categories']['terms'][$verticalTerm['slug']]['current'] = true;

					} else {
						// let's find the child
						foreach($verticalTerm['terms'] as $term) {
							if($term['slug'] === $this->category->slug) {
								$this->filters['categories']['terms'][$verticalTerm['slug']]['terms'][$this->category->slug]['current'] = true;
							}
						}
					}

					break;
 				}
			}
		} 
		else {
			foreach($this->filters['categories']['terms'] as $postType) {
				if($this->postType->name === $postType['slug']) {
					$this->filters['categories']['terms'][$postType['slug']]['currentParent'] = true;

					// now see if the vertical is the current parent or actually the current one
					// if the current category is vertical, then we don't have a child category right now
					if($this->category->taxonomy === 'verticals') {
						$this->filters['categories']['terms'][$postType['slug']]['current'] = true;

					} else {
						// let's find the child
						foreach($postType['terms'] as $term) {
							if($term['slug'] === $this->category->slug) {
								$this->filters['categories']['terms'][$postType['slug']]['terms'][$this->category->slug]['current'] = true;
							}
						}
					}

					break;
 				}
			}
		}
    }
}


