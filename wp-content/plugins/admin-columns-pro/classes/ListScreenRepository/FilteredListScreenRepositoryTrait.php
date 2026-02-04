<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use AC\ListScreenCollection;
use AC\ListScreenRepository\Filter;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use AC\Type\TableId;

trait FilteredListScreenRepositoryTrait
{

    protected function find_from_source(ListScreenId $id): ?ListScreen
    {
        $list_screens = (new Filter\ListScreenId($id))->filter(
            $this->find_all_from_source()
        );

        return $list_screens->count()
            ? $list_screens->first()
            : null;
    }

    protected function find_all_by_table_id_from_source(
        TableId $id,
        ?ListScreenStatus $type = null
    ): ListScreenCollection {
        $list_screens = (new Filter\TableScreenId($id))->filter(
            $this->find_all_from_source()
        );

        return $type
            ? (new Filter\ListScreenStatus($type))->filter($list_screens)
            : $list_screens;
    }

    abstract protected function find_all_from_source(): ListScreenCollection;

}