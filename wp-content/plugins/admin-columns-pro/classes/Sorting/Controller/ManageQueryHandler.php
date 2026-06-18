<?php

declare(strict_types=1);

namespace ACP\Sorting\Controller;

use AC\ListScreen;
use AC\Type\ColumnId;
use ACP\Query\QueryRegistry;
use ACP\Sorting\ModelFactory;
use ACP\Sorting\Type\Order;

class ManageQueryHandler
{

    private ListScreen $list_screen;

    private ModelFactory $model_factory;

    public function __construct(ListScreen $list_screen, ModelFactory $model_factory)
    {
        $this->list_screen = $list_screen;
        $this->model_factory = $model_factory;
    }

    public function handle(): void
    {
        $column_name = $_GET['orderby'] ?? '';

        if ( ! ColumnId::is_valid_id($column_name)) {
            return;
        }

        $column = $this->list_screen->get_column(
            new ColumnId($column_name)
        );

        if ( ! $column) {
            return;
        }

        $model = $this->model_factory->create($column, $this->list_screen->get_table_screen());

        if ( ! $model) {
            return;
        }

        $order = Order::create_by_string($_GET['order'] ?? '');

        QueryRegistry::create(
            $this->list_screen->get_table_screen(),
            [
                $model->create_query_bindings($order),
            ]
        )->register();
    }
}