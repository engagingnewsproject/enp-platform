<?php

namespace ACP\Filtering\Model\Post;

use AC;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Sticky extends Search\Comparison\Post\Sticky
{

    public function __construct(AC\Column $column)
    {
        parent::__construct();
    }

}