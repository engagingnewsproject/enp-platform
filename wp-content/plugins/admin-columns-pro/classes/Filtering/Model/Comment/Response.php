<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Response extends Search\Comparison\Comment\Post
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}