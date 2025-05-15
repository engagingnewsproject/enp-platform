<?php

namespace ACP\Filtering\Model\Post;

use ACP\Search;

/**
 * @deprecated 6.4
 */
class Ancestors extends Search\Comparison\Post\Ancestors
{

    public function __construct($column)
    {
        parent::__construct();
    }

}