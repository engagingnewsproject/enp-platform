<?php

namespace ACP\Editing\Encoder;

use AC;
use ACP\Column;
use ACP\Editing\ApplyFilter;
use ACP\Editing\Factory\BulkEditFactory;
use ACP\Editing\Factory\InlineEditFactory;
use ACP\Editing\Service;
use ACP\Editing\View;

/**
 * Get all data settings needed to load editing for the WordPress list table
 */
class TableDataEncoder
{

    private InlineEditFactory $inline_edit_factory;

    private BulkEditFactory $bulk_edit_factory;

    public function __construct(
        InlineEditFactory $inline_edit_factory,
        BulkEditFactory $bulk_edit_factory
    ) {
        $this->inline_edit_factory = $inline_edit_factory;
        $this->bulk_edit_factory = $bulk_edit_factory;
    }

    public function encode(AC\ListScreen $list_screen): array
    {
        $data = [];

        foreach ($this->inline_edit_factory->create($list_screen) as $column) {
            $column_data = $this->create_data_by_column($column, Service::CONTEXT_SINGLE, $list_screen);

            if ($column_data) {
                $data[(string)$column->get_id()]['type'] = $column->get_type();
                $data[(string)$column->get_id()]['inline_edit'] = $column_data;
            }
        }

        foreach ($this->bulk_edit_factory->create($list_screen) as $column) {
            $column_data = $this->create_data_by_column($column, Service::CONTEXT_BULK, $list_screen);

            if ($column_data) {
                $data[(string)$column->get_id()]['type'] = $column->get_type();
                $data[(string)$column->get_id()]['bulk_edit'] = $column_data;
            }
        }

        return $data;
    }

    private function create_data_by_column(AC\Column $column, string $edit_context, AC\ListScreen $list_screen): ?array
    {
        if ( ! $column instanceof Column) {
            return null;
        }

        $service = $column->editing();

        if ( ! $service) {
            return null;
        }

        $context = $column->get_context();
        $filter = new ApplyFilter\View($context, $edit_context, $service, $list_screen->get_table_screen());

        $view = $filter->apply_filters(
            $service->get_view($edit_context)
        );

        if ( ! $view instanceof View) {
            return null;
        }

        $data = $view->get_args();

        if (isset($data['options'])) {
            $data['options'] = $this->encode_options($data['options']);
        }

        return $data;
    }

    private function encode_options(array $list): array
    {
        $encoded = [];

        if ($list) {
            foreach ($list as $index => $option) {
                if (is_scalar($option)) {
                    $encoded[] = [
                        'value' => $index,
                        'label' => html_entity_decode($option),
                    ];
                    continue;
                }

                $options = $option['options'] ?? null;

                if (is_array($options)) {
                    $option['options'] = $this->encode_options($options);
                    $encoded[] = $option;
                }
            }
        }

        return $encoded;
    }

}