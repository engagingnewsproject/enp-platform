<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Service;

use AC\Registerable;
use ACP\ConditionalFormat\Settings\ListScreen\TableElementFactory;
use ACP\Settings\ListScreen\TableElements;

final class ListScreenSettings implements Registerable
{

    private TableElementFactory $table_elements_factory;

    public function __construct(TableElementFactory $table_elements_factory)
    {
        $this->table_elements_factory = $table_elements_factory;
    }

    public function register(): void
    {
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements']);
    }

    public function add_table_elements(TableElements $collection): void
    {
        $collection->add($this->table_elements_factory->create(), 55);
    }

}