<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search\Comparison\Comment\Url;

/**
 * @deprecated 6.4
 */
class AuthorUrl extends Url
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}