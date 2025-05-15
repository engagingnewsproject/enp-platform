<?php

namespace ACP\Filtering\Model\Post;

use AC;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Taxonomy extends Search\Comparison\Post\Taxonomy
{

    public function __construct(AC\Column $column)
    {
        parent::__construct($column->get_taxonomy());
    }

}