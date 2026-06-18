<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class SoldProducts implements Formatter
{

    public function format(Value $value)
    {
        $products = $this->get_products((int)$value->get_value());

        if (empty($products)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $count = 0;

        foreach ($products as $product) {
            $count += $product->qty;
        }

        return $value->with_value($count);
    }

    private function get_products(int $id): array
    {
        global $wpdb;

        // Unique products
        $sql_parts = [
            'select' => '
				SELECT oim.meta_value AS product_id, pm.post_id AS order_id, oim2.meta_value as qty',
            'from'   => "
				FROM $wpdb->postmeta AS pm",
            'joins'  => [
                "
				INNER JOIN $wpdb->posts AS p 
					ON p.ID = pm.post_id 
					AND p.post_status = 'wc-completed'",
                "
				INNER JOIN {$wpdb->prefix}woocommerce_order_items AS oi
					ON oi.order_id = p.ID 
					AND oi.order_item_type = 'line_item'",
                "
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
					ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'",
                "
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim2 
					ON oi.order_item_id = oim2.order_item_id 
					AND oim2.meta_key = '_qty'",
            ],
            'where'  => "WHERE pm.meta_key = '_customer_user'
				AND pm.meta_value = %d",
        ];

        $sql = $this->built_sql($sql_parts);

        $stmt = $wpdb->prepare($sql, [$id]);
        $results = $wpdb->get_results($stmt);

        if (empty($results)) {
            return [];
        }

        return $results;
    }

    private function built_sql(array $parts): string
    {
        $sql = '';

        foreach ($parts as $part) {
            if (is_array($part)) {
                $sql .= $this->built_sql($part);
            } else {
                $sql .= ' ' . $part;
            }
        }

        return $sql;
    }

}