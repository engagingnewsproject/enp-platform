<?php

namespace ACP\Filtering\Model\Comment;

use AC\Column;
use ACP\Search\Comparison\Comment\Email;

/**
 * @deprecated 6.4
 */
class AuthorEmail extends Email
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}