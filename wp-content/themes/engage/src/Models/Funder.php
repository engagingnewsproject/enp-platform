<?php
namespace Engage\Models;

class Funder extends Article {

	public $featured = false;

	public function __construct($postID = null)
    {
        parent::__construct($postID);
        $this->featured = has_term('featured', 'funders_category', $this->ID);
    }
}