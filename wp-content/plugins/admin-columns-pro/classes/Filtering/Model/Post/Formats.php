<?php

namespace ACP\Filtering\Model\Post;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Formats extends Search\Comparison\Post\Taxonomy
{

    public function __construct(Column $column)
    {
        parent::__construct('post_format');
    }

}