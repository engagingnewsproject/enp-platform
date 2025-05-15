<?php

namespace ACP\Filtering\Model\Media;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Comments extends Search\Comparison\Post\CommentCount
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}