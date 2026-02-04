<?php

declare(strict_types=1);

namespace ACA\BP\Export\Strategy;

use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;

class Group extends Strategy
{

    private ResponseFactory $response_factory;

    private TableDataFactory $table_data_factory;

    public function __construct(
        TableDataFactory $table_data_factory,
        ResponseFactory $response_factory
    ) {
        $this->table_data_factory = $table_data_factory;
        $this->response_factory = $response_factory;
    }

    public function handle_export(): void
    {
        ob_start();

        add_filter('bp_after_groups_get_groups_parse_args', [$this, 'parse_args']);
        add_filter('groups_get_groups', [$this, 'catch_posts'], 1000, 2);
    }

    private function is_main_query($args): bool
    {
        if (is_array($args['include']) && $args['include'][0] === 0) {
            return false;
        }

        return $args['fields'] === 'all';
    }

    public function parse_args($args): array
    {
        if ( ! $this->is_main_query($args)) {
            return $args;
        }

        $args['per_page'] = $this->items_per_iteration;
        $args['page'] = $this->counter + 1;

        return $args;
    }

    public function catch_posts($groups, $args): ?array
    {
        if ( ! $this->is_main_query($args)) {
            return $groups;
        }

        ob_get_clean();

        $values = array_map(static function ($group) {
            return new Value((int)$group->id);
        }, $groups['groups']);

        $table_data = $this->table_data_factory->create(
            $this->columns,
            new ValueCollection(0, $values),
            0 === $this->counter
        );

        $this->response_factory->create(
            $table_data
        );

        return null;
    }

}