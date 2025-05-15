<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Approved extends Search\Comparison\Comment\Approved
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}