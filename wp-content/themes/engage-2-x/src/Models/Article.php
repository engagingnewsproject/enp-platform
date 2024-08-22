<?php
namespace Engage\Models;

use Timber\Post;

/**
 * Class Article
 *
 * Extends Timber's Post class to add custom functionality for articles.
 *
 * @package Engage\Models
 */
class Article extends Post
{

    /**
     * The vertical associated with the article.
     *
     * @var mixed
     */
    public $vertical;

    /**
     * Initializes the Article object.
     *
     * Calls the parent constructor from Timber\Post to initialize the article with an optional post ID.
     *
     * @param int|null $postID The ID of the post to initialize. Defaults to null.
     */
    public function init($postID = null)
    {
        parent::__construct($postID);
    }
}
