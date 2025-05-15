<?php

namespace ACA\WC\Search\OrderSubscription;

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

    /**
     * @var string
     */
    private $post_type;

    public function __construct($post_type = 'shop_order')
    {
        $operators = new ACP\Search\Operators([
            ACP\Search\Operators::EQ,
            ACP\Search\Operators::NEQ,
        ]);

        $this->post_type = $post_type;

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        return (new Bindings())->where($this->get_where($value->get_value(), $operator));
    }

    /**
     * @param int $product_id
     *
     * @return string
     */
    public function get_where($product_id, $operator)
    {
        global $wpdb;
        $orders = $this->get_orders_ids_by_product_id((int)$product_id);
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

        $results = $wpdb->get_col(
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

        return $results;
    }

}