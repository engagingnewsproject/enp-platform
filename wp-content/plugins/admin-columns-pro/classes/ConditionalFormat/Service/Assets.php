<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Service;

use AC;
use AC\Asset\Location;
use AC\Column;
use AC\ColumnIterator;
use AC\ColumnNamesTrait;
use AC\ListScreen;
use AC\Registerable;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\ColumnRepository\FilterByConditionalFormat;
use ACP\ConditionalFormat\Operators;
use ACP\ConditionalFormat\Settings\ListScreen\TableElementFactory;

final class Assets implements Registerable
{

    use Column\ColumnLabelTrait;

    use ColumnNamesTrait;

    private Location\Absolute $location;

    private Operators $operators;

    private TableElementFactory $table_element_factory;

    private ConditionalFormat\ActiveRulesResolver $active_rules_resolver;

    public function __construct(
        Location\Absolute $location,
        Operators $operators,
        TableElementFactory $table_element_factory,
        ConditionalFormat\ActiveRulesResolver $active_rules_resolver
    ) {
        $this->location = $location;
        $this->operators = $operators;
        $this->table_element_factory = $table_element_factory;
        $this->active_rules_resolver = $active_rules_resolver;
    }

    private function is_enabled(ListScreen $list_screen): bool
    {
        return $this->table_element_factory->create()->is_enabled($list_screen);
    }

    private function get_column_labels(ColumnIterator $columns): array
    {
        $data = [];

        foreach ($columns as $column) {
            $data[(string)$column->get_id()] = [
                'label'     => $this->get_column_label($column),
                'operators' => [],
            ];
        }

        return $data;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', function (ListScreen $list_screen) {
            if ( ! $this->is_enabled($list_screen)) {
                return;
            }

            $filter = new FilterByConditionalFormat();
            $columns = $filter->filter($list_screen->get_columns());
            $rules = $this->active_rules_resolver->find($list_screen);

            $assets = [
                new ConditionalFormat\Asset\Table(
                    $this->location,
                    $this->operators,
                    $this->get_column_labels($columns),
                    $rules ? $rules->get_key() : null
                ),
                new AC\Asset\Style(
                    'acp-cf-table',
                    $this->location->with_suffix('assets/conditional-format/css/table.css')
                ),
            ];

            foreach ($assets as $asset) {
                $asset->enqueue();
            }
        });
    }

}