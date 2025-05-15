<?php

namespace ACP\Filtering\Model\Media;

use ACP\Search;

/**
 * @deprecated 6.4
 */
class Author extends Search\Comparison\Post\Author
{

    public function __construct()
    {
        parent::__construct('attachment');
    }

}