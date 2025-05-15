<?php

namespace ACP\Filtering\Model\Media;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class MimeType extends Search\Comparison\Media\MimeType
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }
}