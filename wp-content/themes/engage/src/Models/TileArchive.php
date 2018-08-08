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
    		'taxonomyStructure'  => 'sections',
    		'postTypes'  => [],
    		'filters'    => []
    	];

    	$options = array_merge($defaults, $options);


    	$this->queriedObject = get_queried_object();
        $this->taxonomies = $options['taxonomies'];
        $this->postTypes = $options['postTypes'];
        $this->taxonomyStructure = $options['taxonomyStructure'];
        $this->filters = $options['filters'];


        if(empty($this->taxonomies)) {
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
        } 
        

        $query = [
        	'post_type' => $this->postTypes, 
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
        }

        parent::__construct($query);

        if(empty($this->filters)) {
        	$options = [
	        	'taxonomies' => $this->taxonomies,
	            'taxonomyStructure'  => $this->taxonomyStructure,
	            'postTypes'  => $this->postTypes,
	            'posts'	     => $this->posts
	        ];
        	$this->filters = new FilterMenu($options);

	        $this->filters = $this->filters->build();
        }
    }

    
}


