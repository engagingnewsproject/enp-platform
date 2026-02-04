<?php

namespace ACP\Export\Strategy;

use AC\Type\ValueCollection;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use WP_User_Query;

class User extends Strategy
{

    private TableDataFactory $table_data_factory;

    private ResponseFactory $response_factory;

    public function __construct(TableDataFactory $table_data_factory, ResponseFactory $response_factory)
    {
        $this->table_data_factory = $table_data_factory;
        $this->response_factory = $response_factory;
    }

    public function handle_export(): void
    {
        add_filter('users_list_table_query_args', [$this, 'catch_users_query'], PHP_INT_MAX - 100);
    }

    /**
     * Modify the users query to use the correct pagination arguments, and export the resulting
     * items. This should be attached to the users_list_table_query_args hook when an AJAX request
     * is sent
     */
    public function catch_users_query($args): void
    {
        $args['offset'] = $this->counter * $this->items_per_iteration;
        $args['number'] = $this->items_per_iteration;
        $args['fields'] = 'ids';

        if ($this->ids) {
            $args['include'] = isset($args['include']) && is_array($args['include'])
                ? array_merge($this->ids, $args['include'])
                : $this->ids;
        }

        $query = new WP_User_Query($args);

        $data = $this->table_data_factory->create(
            $this->columns,
            ValueCollection::from_ids(0, $query->get_results()),
            0 === $this->counter
        );

        $this->response_factory->create(
            $data
        );
        exit;
    }

}