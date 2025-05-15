<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Type extends Search\Comparison\Comment\Type
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}