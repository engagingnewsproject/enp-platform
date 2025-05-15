<?php

namespace ACP\Column\User;

use AC;
use ACP\Editing;
use ACP\Export;
use ACP\Search;
use ACP\Sorting;

/**
 * @since 4.0.7
 */
class Name extends AC\Column\User\Name
    implements Sorting\Sortable, Export\Exportable, Search\Searchable, Editing\Editable
{

    public function sorting()
    {
        return new Sorting\Model\User\FullName();
    }

    public function export()
    {
        return new Export\Model\User\FullName();
    }

    public function search()
    {
        return new Search\Comparison\User\Name(['first_name', 'last_name']);
    }

    public function editing()
    {
        return new Editing\Service\User\FullName();
    }

}