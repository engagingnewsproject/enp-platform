<?php

namespace ACP\Filtering;

use ACP\Search\Comparison;

/**
 * @depecated 6.4
 */
interface Filterable
{

    /**
     * @return Comparison|null
     */
    public function filtering();

}