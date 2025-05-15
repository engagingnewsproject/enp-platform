<?php

namespace ACP\Filtering\Model\Taxonomy;

use ACP\Search;

/**
 * @deprecated 6.4
 */
class ID extends Search\Comparison\Taxonomy\ID
{

    public function __construct($column)
    {
        parent::__construct();
    }

}