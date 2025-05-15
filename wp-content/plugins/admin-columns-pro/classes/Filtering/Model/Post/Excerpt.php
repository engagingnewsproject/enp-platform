<?php

namespace ACP\Filtering\Model\Post;

use AC;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Excerpt extends Search\Comparison\Post\Excerpt
{

    public function __construct(AC\Column $column)
    {
        parent::__construct();
    }

}