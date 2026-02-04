<?php

declare(strict_types=1);

namespace ACP\Export\Asset;

use AC\Column;
use AC\ColumnIterator;
use AC\ColumnNamesTrait;
use AC\ColumnRepository\Sort\ColumnNames;
use AC\ColumnRepository\Sort\ManualOrder;
use AC\ListScreen;
use AC\Type\ListScreenId;
use ACP\ColumnRepository;
use ACP\Export\Repository\UserColumnStateRepository;

class Encoder
{

    use ColumnNamesTrait;

    private UserColumnStateRepository $column_state_repository;

    private ColumnRepository $column_repository;

    public function __construct(
        UserColumnStateRepository $column_state_repository,
        ColumnRepository $column_repository
    ) {
        $this->column_state_repository = $column_state_repository;
        $this->column_repository = $column_repository;
    }

    public function encode(ListScreen $list_screen): array
    {
        $vars = [];

        $active_column_names = $this->get_active_column_names($list_screen);

        foreach ($this->get_exportable_columns($list_screen) as $column) {
            $vars[] = [
                'name'          => (string)$column->get_id(),
                'label'         => $this->get_sanitized_label($column),
                'default_state' => in_array((string)$column->get_id(), $active_column_names, true) ? 'on' : 'off',
            ];
        }

        return $vars;
    }

    private function get_active_column_names(ListScreen $list_screen): array
    {
        $user_exported_column_names = $this->get_user_exported_column_names_active($list_screen);
        $exportable_column_names = $this->get_exportable_column_names($list_screen);

        if ( ! $user_exported_column_names) {
            $hidden_column_names = get_hidden_columns($list_screen->get_screen_id());

            return array_diff(
                $exportable_column_names,
                $hidden_column_names
            );
        }

        // add columns that have been added at a later time through the column settings page
        $missing_column_names = array_diff(
            $exportable_column_names,
            $this->get_user_exported_column_names($list_screen->get_id())
        );

        return array_merge($user_exported_column_names, $missing_column_names);
    }

    private function get_exportable_columns(ListScreen $list_screen): ColumnIterator
    {
        static $columns;

        if (null === $columns) {
            $columns = $this->column_repository->find_all_with_export($list_screen);

            $column_names = $this->get_user_exported_column_names($list_screen->get_id());

            $sort = $column_names
                ? new ColumnNames($column_names)
                : new ManualOrder($list_screen->get_id());

            $columns = $sort->sort($columns);
        }

        return $columns;
    }

    private function get_exportable_column_names(ListScreen $list_screen): array
    {
        return $this->get_column_names_from_collection($this->get_exportable_columns($list_screen));
    }

    private function get_user_exported_column_names_active(ListScreen $list_screen): array
    {
        $column_names = [];

        foreach ($this->column_state_repository->find_all_active_by_list_id($list_screen->get_id()) as $state) {
            $column_names[] = $state->get_column_name();
        }

        return $column_names;
    }

    private function get_user_exported_column_names(ListScreenId $list_id): array
    {
        $column_names = [];

        foreach ($this->column_state_repository->find_all_by_list_id($list_id) as $state) {
            $column_names[] = $state->get_column_name();
        }

        return $column_names;
    }

    private function get_sanitized_label(Column $column): string
    {
        $label_setting = $column->get_setting('label');
        $column_label = $label_setting
            ? $label_setting->get_input()->get_value()
            : $column->get_label();

        return $this->sanitize_column_label($column_label)
            ?: sprintf(
                '%s (%s)',
                $column->get_id(),
                $column_label
            );
    }

    private function sanitize_column_label(string $label): string
    {
        if (false === strpos($label, 'dashicons')) {
            $label = strip_tags($label);
        }

        return trim($label);
    }

}