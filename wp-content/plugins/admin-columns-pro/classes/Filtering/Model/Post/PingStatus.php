<?php

namespace ACP\Filtering\Model\Post;

use AC;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class PingStatus extends Search\Comparison\Post\PingStatus
{

    public function __construct(AC\Column $column)
    {
        parent::__construct();
    }

}