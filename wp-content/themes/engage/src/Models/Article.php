<?php
namespace Engage\Models;

use Timber\Post;
use Timber;

class Article extends Post {

	public $vertical;

	public function init($postID = null)
    {
        parent::__construct($postID);

        // set the vertical attached to this post
				if( isset(get_the_terms($this->ID, 'verticals')[0]->term_id)){
						$this->vertical = new \Engage\Models\VerticalTerm(get_the_terms($this->ID, 'verticals')[0]->term_id);
				}
    }
}
