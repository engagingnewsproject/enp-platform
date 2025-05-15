<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Author extends Search\Comparison\Comment\Author
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}