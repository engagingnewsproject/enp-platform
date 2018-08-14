<?php
/**
* Set data needed for tile layout page
*/
namespace Engage\Models;

class TileArchive extends Archive
{
	public $filters = [],
		   $postTypes = [],
		   $taxonomies = [],
		   $disallowedTaxonomies = ['post_tags', 'research-tags'],
		   $taxonomyStructure = 'vertical'; // when you want things organized by vertical

    public function __construct($options, $query = false)
    {

    	$defaults = [
    		'taxonomies' => [],
    		'taxonomyStructure'  => ( get_query_var('taxonomy_structure') ? get_query_var('taxonomy_structure') : 'sections'),
    		'postTypes'  => [],
    		'filters'    => []
    	];

    	$options = array_merge($defaults, $options);


    	$this->queriedObject = get_queried_object();
        $this->taxonomies = $options['taxonomies'];
        $this->postTypes = $options['postTypes'];
        $this->taxonomyStructure = $options['taxonomyStructure'];
        $this->filters = $options['filters'];

        /*if(empty($this->taxonomies)) {
        	// set smart defaults based on the post_type/taxonomy archive we're on
        	if(get_class($this->queriedObject) === 'WP_Post_Type') {
        		$this->postTypes[] = $this->queriedObject->name;
        	}
        	elseif(get_class($this->queriedObject) === 'WP_Term') {
        		// get the post type this is registered for, then set the taxonomies off of that
        		$tax = get_taxonomy($this->queriedObject->taxonomy);
        		foreach($tax->object_type as $postType) {
        			$this->postTypes[] = $postType;
        		}
        	}
        } */
        
        /*
        if($this->postTypes !== 'post') {

        }
        $query = [
        	//'post_type' => $this->postTypes, 
        	'posts_per_page' => -1
        ];

        if(get_class($this->queriedObject) === 'WP_Term') {
        	$query['tax_query'] = [
				[
					'taxonomy' => $this->queriedObject->taxonomy,
					'field'    => 'slug',
					'terms'    => $this->queriedObject->slug,
				]
			];
        }*/
        parent::__construct($query);

        if(empty($this->filters)) {
        	$options = [
	        	'taxonomies' => $this->taxonomies,
	            'taxonomyStructure'  => $this->taxonomyStructure,
	            'postTypes'  => $this->postTypes,
	            'posts'	     => $this->posts
	        ];
        	$filters = new FilterMenu($options);

	        $this->filters = $filters->build();
	    }

	    $this->setCurrentFilter();
    }


    // set the current filter based on the archive
    public function setCurrentFilter() {
    	// do we have a vertical query in there?
    	$verticalGET = get_query_var( 'verticals', false );
    	// find the category
    	$categoryGET = false;
    	if(get_query_var('post_type') === 'research') {
    		$categoryGET = get_query_var( 'research-categories', false );
    	}
    	
    	// search for the term
		if($this->filters['categories']['structure'] === 'vertical') {
			foreach($this->filters['categories']['terms'] as $vertical) {
				if($verticalGET === $vertical['slug']) {
					$this->filters['categories']['terms'][$vertical['slug']]['currentParent'] = true;


					// now see if the vertical is the current parent or actually the current one
					if(!$categoryGET) {
						$this->filters['categories']['terms'][$vertical['slug']]['current'] = true;

					}
					else if($categoryGET) {
						// let's find the child
						foreach($vertical['terms'] as $term) {
							if($term['slug'] === $categoryGET) {
								$this->filters['categories']['terms'][$vertical['slug']]['terms'][$categoryGET]['current'] = true;


							}
						}
					}

					break;
 				}
			}
		} 
		else {
			if($this->queriedObject->taxonomy !== 'verticals') {
				// let's find the child
				/*foreach($this->vertical['terms'] as $term) {
					if($term['slug'] === $this->queriedObject->slug) {
						$this->filters['categories']['terms'][$vertical['slug']]['terms'][$this->queriedObject->slug]['current'] = true;

					}
				}*/
			}

		}
    }
}


