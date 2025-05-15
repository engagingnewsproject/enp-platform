<?php

namespace ACP\Filtering\Model\Media;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class PostType extends Search\Comparison\Media\PostType
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}