<?php

namespace ACP\Filtering\Model\Post;

use ACP\Search;

/**
 * @deprecated 6.4
 */
class ID extends Search\Comparison\Post\ID
{

    public function __construct($column)
    {
        parent::__construct();
    }

}