<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Search\OrderSubscription;

use ACA\WC\Helper\Select;
use ACA\WC\Scheme\Orders;
use ACP;
use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Value;

class Product extends Comparison
    implements Comparison\SearchableValues
{

    use Select\ProductAndVariationValuesTrait;

    public function __construct()
    {
        $operators = new ACP\Search\Operators([
            ACP\Search\Operators::EQ,
            ACP\Search\Operators::NEQ,
        ]);

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        return (new Bindings())->where(
            $this->get_where((int)$value->get_value(), $operator)
        );
    }

    public function get_where(int $product_id, string $operator): string
    {
        global $wpdb;
        $orders = $this->get_orders_ids_by_product_id($product_id);
        $order_table = $wpdb->prefix . Orders::TABLE;

        if (empty($orders)) {
            $orders = [0];
        }

        $in_operator = $operator === ACP\Search\Operators::NEQ ? 'NOT IN' : 'IN';

        return sprintf("{$order_table}.id %s( %s )", $in_operator, implode(',', $orders));
    }

    /**
     * Get All orders IDs for a given product ID.
     */
    protected function get_orders_ids_by_product_id(int $product_id): array
    {
        global $wpdb;

        $order_table = $wpdb->prefix . Orders::TABLE;

        return $wpdb->get_col(
            $wpdb->prepare(
                "
	        SELECT DISTINCT order_items.order_id
	        FROM {$wpdb->prefix}woocommerce_order_items as order_items
	         JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
	         JOIN $order_table AS orders ON order_items.order_id = orders.id
	        AND order_items.order_item_type = 'line_item'
	        AND ( order_item_meta.meta_key = '_product_id' OR order_item_meta.meta_key = '_variation_id' )
	        AND order_item_meta.meta_value = %s
        ",
                $product_id
            )
        );
    }

}