<?php

namespace ACA\WC\Search\Order;

use ACA\WC\Helper\Select;
use ACP;
use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Value;

class ProductNonAnalytics extends Comparison
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
        return (new Bindings())->where($this->get_where($value->get_value(), $operator));
    }

    public function get_where(int $product_id, string $operator): string
    {
        global $wpdb;
        $orders = $this->get_orders_ids_by_product_id((int)$product_id);

        if (empty($orders)) {
            $orders = [0];
        }

        $in_operator = $operator === ACP\Search\Operators::NEQ ? 'NOT IN' : 'IN';

        return sprintf("{$wpdb->prefix}wc_orders.id %s( %s )", $in_operator, implode(',', $orders));
    }

    /**
     * Get All orders IDs for a given product ID.
     */
    protected function get_orders_ids_by_product_id(int $product_id): array
    {
        global $wpdb;

        $results = $wpdb->get_col(
            $wpdb->prepare(
                "
	        SELECT DISTINCT(order_items.order_id)
	        FROM {$wpdb->prefix}woocommerce_order_items as order_items
	        JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
	        AND order_items.order_item_type = 'line_item'
	        AND ( order_item_meta.meta_key = '_product_id' OR order_item_meta.meta_key = '_variation_id' )
	        AND order_item_meta.meta_value = %s
        ",
                $product_id
            )
        );

        return $results;
    }

}