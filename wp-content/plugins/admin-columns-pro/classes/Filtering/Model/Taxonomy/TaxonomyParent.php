<?php

namespace ACP\Filtering\Model\Taxonomy;

use AC\Column;
use ACP;

/**
 * @deprecated 6.4
 */
class TaxonomyParent extends ACP\Column\Taxonomy\ParentTerm
{

    public function __construct(Column $column)
    {
        parent::__construct($column->get_taxonomy());
    }

}