<?php

namespace ACP\Filtering\Model\Post;

use AC\Column;
use ACP\Search\Comparison;

/**
 * @deprecated 6.4
 */
class AuthorName extends Comparison\Post\Author
{

    public function __construct(Column $column)
    {
        parent::__construct($column->get_post_type());
    }

}