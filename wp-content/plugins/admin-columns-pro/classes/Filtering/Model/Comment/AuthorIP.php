<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search\Comparison\Comment\IP;

/**
 * @deprecated 6.4
 */
class AuthorIP extends IP
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}