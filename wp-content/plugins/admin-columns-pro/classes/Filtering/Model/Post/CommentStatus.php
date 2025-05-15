<?php

namespace ACP\Filtering\Model\Post;

use AC;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class CommentStatus extends Search\Comparison\Post\CommentStatus
{

    public function __construct(AC\Column $column)
    {
        parent::__construct();
    }

}