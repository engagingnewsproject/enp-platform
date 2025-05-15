<?php

namespace ACP\Filtering\Model\Media;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class UsedAsFeaturedImage extends Search\Comparison\Media\UsedAsFeaturedImage
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}