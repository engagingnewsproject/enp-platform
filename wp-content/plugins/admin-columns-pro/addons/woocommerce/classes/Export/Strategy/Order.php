<?php

declare(strict_types=1);

namespace ACA\WC\Export\Strategy;

use AC\Type\ValueCollection;
use ACA\WC\Search\Query\OrderQueryController;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery;

class Order extends Strategy
{

    private string $order_type;

    private ResponseFactory $response_factory;

    private TableDataFactory $table_data_factory;

    public function __construct(
        TableDataFactory $table_data_factory,
        ResponseFactory $response_factory,
        string $order_type = 'shop_order'
    ) {
        $this->table_data_factory = $table_data_factory;
        $this->response_factory = $response_factory;
        $this->order_type = $order_type;
    }

    public function handle_export(): void
    {
        ob_start();
        add_filter('woocommerce_order_list_table_prepare_items_query_args', [$this, 'catch_posts'], 1000);
        add_filter('woocommerce_orders_table_query_clauses', [$this, 'alter_clauses'], 100, 3);
    }

    public function alter_clauses($clauses, OrdersTableQuery $query, $args): array
    {
        if ( ! OrderQueryController::is_main_query($args)) {
            return $clauses;
        }

        $ids = $this->ids;

        if ($ids) {
            $column = $query->get_table_name('orders') . '.ID';
            $ids = array_map('absint', $ids);

            $clauses['where'] .= sprintf(' AND %s IN( %s )', $column, implode(',', $ids));
        }

        return $clauses;
    }

    public function catch_posts($args): void
    {
        ob_get_clean();

        $args['return'] = 'ids';
        $args['type'] = $this->order_type;
        $args['page'] = $this->counter + 1;
        $args['limit'] = $this->items_per_iteration;

        $orders = wc_get_orders($args);

        if (is_object($orders)) {
            $orders = $orders->orders;
        }

        $table_data = $this->table_data_factory->create(
            $this->columns,
            ValueCollection::from_ids(0, $orders),
            0 === $this->counter
        );

        $this->response_factory->create(
            $table_data
        );

        exit;
    }

}