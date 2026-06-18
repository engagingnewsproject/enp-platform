<?php

namespace ACP\Export\Service;

use AC;
use AC\Registerable;
use ACP\Export\Strategy\AggregateFactory;
use ACP\Export\TableElement;
use ACP\Settings\ListScreen\TableElements;

final class Admin implements Registerable
{

    private AggregateFactory $strategy_factory;

    public function __construct(AggregateFactory $strategy_factory)
    {
        $this->strategy_factory = $strategy_factory;
    }

    public function register(): void
    {
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements'], 10, 2);
    }

    public function add_table_elements(TableElements $collection, AC\TableScreen $table_screen): void
    {
        if ($this->strategy_factory->create($table_screen)) {
            $collection->add(new TableElement\Export(), 50);
        }
    }

}