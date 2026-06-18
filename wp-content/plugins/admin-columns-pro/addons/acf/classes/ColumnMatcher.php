<?php

declare(strict_types=1);

namespace ACA\ACF;

use AC\Column;
use AC\ListScreen;
use AC\Type\TableScreenContext;
use ACA\ACF\Field\Type\Repeater;

class ColumnMatcher
{

    private ColumnFactories\FieldFactory $column_factory;

    public function __construct(ColumnFactories\FieldFactory $column_factory)
    {
        $this->column_factory = $column_factory;
    }

    public function find_column(TableScreenContext $table_context, ListScreen $list_screen, Field $root_field, string $field_key): ?Column
    {
        $column_factory = $this->column_factory->create($table_context, $root_field);

        if ( ! $column_factory) {
            return null;
        }

        $column_type = $column_factory->get_column_type();

        /**
         * @var Column $column
         */
        foreach ($list_screen->get_columns() as $column) {
            if ($column_type !== $column->get_type()) {
                continue;
            }

            // Repeater
            if ($root_field instanceof Repeater) {
                if ($column->get_context()->get('sub_field') === $field_key) {
                    return $column;
                }

                continue;
            }

            // Others
            return $column;
        }

        return null;
    }

}
