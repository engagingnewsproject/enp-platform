<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\ManageValue;

use AC\Table\ManageValue\RenderFactory;
use AC\TableScreen\ManageValueService;
use AC\Type\ColumnId;
use AC\Type\Value;

class Activity implements ManageValueService
{

    private RenderFactory $factory;

    public function __construct(RenderFactory $factory)
    {
        $this->factory = $factory;
    }

    public function register(): void
    {
        add_filter('bp_activity_admin_get_custom_column', [$this, 'render_value'], 100, 3);
    }

    public function render_value(...$args)
    {
        [$value, $column_name, $group] = $args;

        $formatter = $this->factory->create(new ColumnId((string)$column_name));

        return $formatter
            ? (string)$formatter->format(new Value((int)$group['id']))
            : $value;
    }

}