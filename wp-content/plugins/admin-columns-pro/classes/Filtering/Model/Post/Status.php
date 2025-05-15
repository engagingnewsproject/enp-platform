<?php

namespace ACP\Filtering\Model\Post;

use AC;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Status extends Search\Comparison\Post\Status
{

    public function __construct(AC\Column $column)
    {
        parent::__construct($column->get_post_type());
    }

}