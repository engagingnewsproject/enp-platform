<?php

namespace ACP\Filtering\Model\User;

use AC\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Url extends Search\Comparison\User\Url
{

    public function __construct(Column $column)
    {
        parent::__construct();
    }

}