<?php

declare(strict_types=1);

namespace ACP\Filtering\Asset;

use AC;
use AC\Asset\Location;
use AC\Asset\Script\Localize\Translation;
use AC\Helper\Select\ArrayMapper;
use AC\Request;
use AC\Type\ColumnId;
use ACP\Column;
use ACP\Filtering;
use ACP\Filtering\OptionsFactory;
use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

final class TableScriptFactory
{

    private $location;

    private $options_factory;

    private $request;

    private array $default_filters;

    public function __construct(
        Location $location,
        OptionsFactory $options_factory,
        Request $request,
        array $default_filters = []
    ) {
        $this->location = $location;
        $this->options_factory = $options_factory;
        $this->request = $request;
        $this->default_filters = $default_filters;
    }

    public function create(AC\ListScreen $list_screen): AC\Asset\Script
    {
        $script = new AC\Asset\Script(
            'acp-filtering-table',
            $this->location->with_suffix('assets/filtering/js/table.js'),
            ['jquery', 'jquery-ui-datepicker', AC\Asset\Script\GlobalTranslationFactory::HANDLE]
        );

        $script->add_inline_variable(
            'acp_filtering',
            [
                'filters'                => $this->get_filters($list_screen),
                'rules'                  => $this->get_rules($list_screen),
                'default_active_filters' => $this->default_filters,
            ]
        );

        $script->localize(
            'acp_filtering_i18n',
            Translation::create([
                'fetching_results'    => __('Fetching options', 'codepress-admin-columns'),
                'label_start_date'    => __('Start date', 'codepress-admin-columns'),
                'label_end_date'      => __('End date', 'codepress-admin-columns'),
                'label_start_number'  => __('Min', 'codepress-admin-columns'),
                'label_end_number'    => __('Max', 'codepress-admin-columns'),
                'filter'              => __('Filter', 'codepress-admin-columns'),
                'no_results'          => __('No options found', 'codepress-admin-columns'),
                'more_search_records' => __('Please enter more characters to narrow down the search results'),
            ])
        );

        return $script;
    }

    private function get_rules(AC\ListScreen $list_screen): array
    {
        $request = $this->request;

        $rules = [];

        foreach ($request->get('acp_filter', []) as $column_name => $value) {
            $column = $list_screen->get_column(
                new ColumnId((string)$column_name)
            );

            if ( ! $column instanceof Column) {
                continue;
            }

            $comparison = $column->search();

            if ( ! $comparison) {
                continue;
            }

            $setting = $column->get_setting('filter');

            if ( ! $setting instanceof AC\Setting\Component || ! $setting->has_input()) {
                continue;
            }

            if ($setting->get_input()->get_value() !== 'on') {
                continue;
            }

            $rules[] = [
                'column_name' => (string)$column->get_id(),
                'value'       => $value,
                'label'       => $this->get_option_label($comparison, $value),
            ];
        }

        return $rules;
    }

    private function get_option_label(Comparison $comparison, $value): ?string
    {
        if ($value === Filtering\EmptyOptions::IS_EMPTY) {
            return $comparison->get_labels()[Operators::IS_EMPTY];
        }

        if ($value === Filtering\EmptyOptions::NOT_IS_EMPTY) {
            return $comparison->get_labels()[Operators::NOT_IS_EMPTY] ?? $value;
        }

        if ($comparison instanceof Comparison\RemoteValues && is_scalar($value)) {
            return $comparison->format_label($value);
        }

        if ($comparison instanceof Comparison\SearchableValues) {
            return $comparison->format_label($value);
        }

        return null;
    }

    private function get_filters(AC\ListScreen $list_screen): array
    {
        $filters = [];

        foreach ($list_screen->get_columns() as $column) {
            if ( ! $column instanceof Column) {
                continue;
            }

            $comparison = $column->search();

            if ( ! $comparison) {
                continue;
            }

            $setting = $column->get_setting('filter');

            if ( ! $setting instanceof AC\Setting\Component) {
                continue;
            }

            if ( ! $setting->has_input() || $setting->get_input()->get_value() !== 'on') {
                continue;
            }

            $filter = [
                'column'        => (string)$column->get_id(),
                'label'         => $this->get_filter_label($column),
                'type'          => $this->get_filter_type($comparison),
                'remote_values' => false,
            ];

            if ($comparison instanceof Comparison\Values) {
                $options = $this->options_factory->create_by_values($comparison);

                if ($options->count() > 0) {
                    $filter['options'] = ArrayMapper::map($options);

                    if ( ! $filter['type']) {
                        $filter['type'] = 'select';
                    }
                }
            }

            if ($this->is_logic_group_only($comparison)) {
                $filter['options'] = ArrayMapper::map($this->options_factory->create_logic_options($comparison));
                $filter['type'] = 'select';
            }

            if ( ! $filter['type']) {
                continue;
            }

            $filter_format = $column->get_setting('filter_format');

            if ($filter['type'] === 'date' && $filter_format) {
                $date_type = $filter_format->get_input()->get_value();

                if (null !== $date_type) {
                    $filter['type'] = $date_type ? sprintf('date_%s', $date_type) : 'date';
                    $filter['remote_values'] = $comparison instanceof Comparison\RemoteValues;
                }

                if ('future_past' === $date_type) {
                    $filter['type'] = 'select';
                    $filter['options'] = ArrayMapper::map(
                        AC\Helper\Select\Options::create_from_array([
                            'future' => __('Future', 'codepress-admin-columns'),
                            'past'   => __('Past', 'codepress-admin-columns'),
                        ])
                    );
                }
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    private function is_logic_group_only(Comparison $comparison): bool
    {
        if ($this->get_filter_type($comparison)) {
            return false;
        }

        return $comparison->get_operators()->search(Operators::IS_EMPTY) ||
               $comparison->get_operators()->search(Operators::NOT_IS_EMPTY);
    }

    private function get_filter_type(Comparison $comparison): ?string
    {
        if ($comparison->get_operators()->search(Operators::BETWEEN)) {
            switch ($comparison->get_value_type()) {
                case Value::INT:
                case Value::DECIMAL:
                    return 'numeric';
                case Value::DATE:
                    return 'date';
            }
        }

        switch (true) {
            case $comparison instanceof Comparison\Values:
                return 'select';
            case $comparison instanceof Comparison\RemoteValues:
                return 'select_remote';
            case $comparison instanceof Comparison\SearchableValues:
                return 'select_search';
        }

        if ($comparison->get_operators()->search(Operators::CONTAINS)) {
            return 'search';
        }

        return null;
    }

    private function get_filter_label(AC\Column $column): string
    {
        $setting = $column->get_setting('filter_label');

        if ( ! $setting) {
            return $column->get_label();
        }

        $label = $setting->get_input()->get_value();

        if ( ! $label) {
            $label_setting = $column->get_setting('label');

            $column_label = $label_setting
                ? trim(strip_tags($label_setting->get_input()->get_value()))
                : '';

            return sprintf(__("Any %s", 'codepress-admin-columns'), $column_label ?: $column->get_label());
        }

        return $label;
    }

}