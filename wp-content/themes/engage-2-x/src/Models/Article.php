<?php
namespace Engage\Models;

use Timber\Post;

class Article extends Post {

	public $vertical;

	public function init($postID = null)
    {
			parent::__construct($postID);
    }
}