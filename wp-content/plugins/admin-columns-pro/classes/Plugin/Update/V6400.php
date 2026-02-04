<?php

declare(strict_types=1);

namespace ACP\Plugin\Update;

use AC\Column;
use AC\ListScreenRepository\Storage;
use AC\Plugin\Update;
use AC\Plugin\Version;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACA;
use DateTime;

final class V6400 extends Update
{

    private Storage $storage;

    public function __construct(Storage $storage)
    {
        parent::__construct(new Version('6.4'));

        $this->storage = $storage;
    }

    public function apply_update(): void
    {
        $this->apply_update_saved_filters();
    }

    private function apply_update_saved_filters(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'ac_segments';

        $sql = "
            SELECT *
            FROM $table
        ";

        $rows = $wpdb->get_results($sql);

        if ( ! $rows || ! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            // Ranged filters (e.g. number and date fields)
            if ($this->contains_ranged_filter($row)) {
                $this->update_ranged_filter($row);
            }

            // Date type is `Daily` (needs a date format conversion)
            if ($this->contains_date_type_daily($row)) {
                $this->update_date_type_daily($row);
            }

            // Filter input is no longer supported (e.g. 'User ID' is the input where 'First Name' is expected)
            if ($this->contains_user_filter($row)) {
                $this->update_user_filter($row);
            }
        }
    }

    private function update_user_filter($row): void
    {
        $parameters = unserialize($row->url_parameters, ['allowed_classes' => false]);

        foreach ($this->get_columns_with_user_filter($row) as $column) {
            // remove segments that are no longer supported. e.g. filtering on a `user ID` where only a 'first name' is a valid input
            unset($parameters['acp_filter'][(string)$column->get_id()]);
        }

        $this->update_url_parameters(
            (int)$row->id,
            $parameters
        );
    }

    private function contains_user_filter($row): bool
    {
        return (bool)$this->get_columns_with_user_filter($row);
    }

    /**
     * @return Column[]
     */
    private function get_columns_with_user_filter($row): array
    {
        $parameters = unserialize($row->url_parameters, ['allowed_classes' => false]);
        $filters = $parameters['acp_filter'] ?? null;

        if ( ! $filters) {
            return [];
        }

        if ( ! ListScreenId::is_valid_id($row->list_screen_id)) {
            return [];
        }

        $list_screen = $this->storage->find(new ListScreenId($row->list_screen_id));

        if ( ! $list_screen) {
            return [];
        }

        $columns = [];

        foreach ($parameters['acp_filter'] as $column_name => $value) {
            $column = $list_screen->get_column(
                new ColumnId((string)$column_name)
            );

            if ($column && $this->is_filter_by_user($column, $value)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function is_filter_by_user(Column $column, $user_id): bool
    {
        if ( ! is_numeric($user_id)) {
            return false;
        }

        if ( ! in_array($column->get_type(), ['column-author_name', 'column-order_customer'], true)) {
            return false;
        }

        $setting = $column->get_setting('display_author_as');

        if ( ! $setting) {
            return false;
        }

        $author_as = $setting->get_input()->get_value();

        if ( ! $author_as || ! is_string($author_as)) {
            return false;
        }

        // Current input (e.g. User ID) does not match with new filter input (e.g. First Name)
        $fields = [
            'first_name',
            'user_email',
            'user_login',
            'user_nicename',
            'user_url',
        ];

        return in_array($author_as, $fields, true);
    }

    private function update_date_type_daily($row): void
    {
        $parameters = unserialize($row->url_parameters, ['allowed_classes' => false]);

        $column_names = $this->get_columns_date_type_daily($row);

        foreach ($parameters['acp_filter'] as $column_name => $value) {
            if ( ! in_array((string)$column_name, $column_names, true)) {
                continue;
            }

            // convert date from 'yyyymmdd' to 'yyyy-mm-dd'
            $parameters['acp_filter'][$column_name] = DateTime::createFromFormat('Ymd', $value)->format('Y-m-d');
        }

        $this->update_url_parameters(
            (int)$row->id,
            $parameters
        );
    }

    private function contains_date_type_daily($row): bool
    {
        return (bool)$this->get_columns_date_type_daily($row);
    }

    private function get_columns_date_type_daily($row): array
    {
        $parameters = unserialize($row->url_parameters, ['allowed_classes' => false]);
        $filters = $parameters['acp_filter'] ?? null;

        if ( ! $filters) {
            return [];
        }

        if ( ! ListScreenId::is_valid_id($row->list_screen_id)) {
            return [];
        }

        $list_screen = $this->storage->find(new ListScreenId($row->list_screen_id));

        if ( ! $list_screen) {
            return [];
        }

        $columns = [];

        foreach ($parameters['acp_filter'] as $column_name => $value) {
            if ( ! ColumnId::is_valid_id((string)$column_name)) {
                continue;
            }

            $column = $list_screen->get_column(
                new ColumnId((string)$column_name)
            );

            if ( ! $column) {
                continue;
            }

            if ($this->is_filter_date_daily($column, $value)) {
                $columns[] = (string)$column->get_id();
            }
        }

        return $columns;
    }

    private function is_filter_date_daily(Column $column, $date): bool
    {
        // daily date format is 'yyyymmdd'
        if ( ! is_string($date) || 8 !== strlen($date)) {
            return false;
        }

        $filter_format = $columns[(string)$column->get_id()]['filter_format'] ?? null;

        // Key for `Daily` is an empty string
        if ('' === $filter_format) {
            return true;
        }

        return false;
    }

    private function contains_ranged_filter($row): bool
    {
        $parameters = unserialize($row->url_parameters, ['allowed_classes' => false]);

        return isset($parameters['acp_filter-min'], $parameters['acp_filter-max']);
    }

    private function update_url_parameters(int $id, array $parameters): void
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'ac_segments',
            [
                'url_parameters' => serialize($parameters),
            ],
            [
                'id' => $id,
            ]
        );
    }

    private function update_ranged_filter($row): void
    {
        $parameters = unserialize($row->url_parameters, ['allowed_classes' => false]);

        // convert format from `acp_filter-min/max` format to `acp_filter`
        $filters = $parameters['acp_filter'];

        foreach ($parameters['acp_filter-min'] as $key => $min_value) {
            $max_value = $parameters['acp_filter-max'][$key] ?? null;
            $filters[$key] = [$min_value, $max_value];
        }

        $parameters['acp_filter'] = $filters;

        unset($parameters['acp_filter-max']);
        unset($parameters['acp_filter-min']);

        $this->update_url_parameters(
            (int)$row->id,
            $parameters
        );
    }

}