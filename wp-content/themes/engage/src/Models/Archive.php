<?php
/**
* Generic post functionality that extends TimberPost
*/
namespace Engage\Models;

use Timber\PostQuery;
use Timber\Pagination;

class Archive extends PostQuery
{
	public $title,
		   $description,
		   $posts,
		   $pagination,
		   $slug;

    public function __construct($query = false)
    {
    	$query = $this->verticalQuery($query);

        parent::__construct($query);
        $this->queriedObject = get_queried_object();
        $this->posts = $this->queryIterator->get_posts();
        $this->pagination = $this->pagination();
        $this->taxonomy = $this->queriedObject->taxonomy;
        $this->slug = $this->queriedObject->slug;
        $this->setTitle();
        $this->setDescription();
    }

 	/**
 	 * Are we limiting this query by Vertical?
 	 */
    public function verticalQuery($query) {
    	// do we have a ?vertical query parameter
        if($_GET['vertical']) {
        	$verticalTaxQuery = [
				'taxonomy' => 'verticals',
				'field'    => 'slug',
				'terms'    => $_GET['vertical'],
			];

        	if(!isset($query['tax_query'])) {
        		$query['tax_query'] = [$verticalTaxQuery];
        	} else {
        		// check to make sure we don't already have this one set
        		$hasVerticalTaxQuery = false;
        		foreach($query['tax_query'] as $taxQuery) {
        			if($taxQuery['taxonomy'] === 'verticals') {
        				$hasVerticalTaxQuery = true;
        			}
        		}
        		if(!$hasVerticalTaxQuery) {
        			$query['tax_query'][] = $verticalTaxQuery;
        		}
        	}
        }

        return $query;
    }

    /**
    * Sets the archive page title
    *
    * @return String
    */
    public function setTitle() {
        $title = 'Archive';
        if ( is_day() ) {
			$title = 'Archive: '.get_the_date( 'D M Y' );
		} else if ( is_month() ) {
			$title = 'Archive: '.get_the_date( 'M Y' );
		} else if ( is_year() ) {
			$title = 'Archive: '.get_the_date( 'Y' );
		} else if ( is_post_type_archive() ) {
			$title = post_type_archive_title( '', false );
		} else if( is_tax() ) {
			$title = $this->queriedObject->name;
		} else if(get_search_query()) {
			$title = 'Search: '. get_search_query();
		}

		$this->title = $title;
	}



	/**
	 * 
	 * @param $format BOOLEAN if you want the description run through wpautop or not
	 * @return String
	 */
	public function setDescription() {
		
		$description = $this->queriedObject->description;

		if(empty($description)) {
			// check if we have one from the settings
			$intros = get_field('archive_landing_pages', 'option');
			
			foreach($intros as $intro) {
				if($intro['landing_slug']['value'] === $queriedObject->name) {
					$context['archive']['intro'] = [
						'title'   => $intro['landing_page_title'],
						'excerpt' => $intro['landing_page_content']
					];
					break;
				}
			}
		}
		$this->description = $description;
	}

	public function getDescription($format = true) {
		return ( $format ? wpautop($this->description) : $this->description );
	}
}
