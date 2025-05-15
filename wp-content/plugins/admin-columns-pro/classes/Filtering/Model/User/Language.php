<?php

namespace ACP\Filtering\Model\User;

use ACP\Column;
use ACP\Search;

/**
 * @deprecated 6.4
 */
class Language extends Search\Comparison\User\Languages
{

    public function __construct(Column\User\Language $column)
    {
        parent::__construct([]);
    }

}