<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class PostType extends Search\Comparison\Comment\PostType
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}