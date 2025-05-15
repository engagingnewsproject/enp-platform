<?php

namespace ACA\WC\Search\Order\Notes;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class CustomerNotes extends Comparison
{

    public function __construct()
    {
        $operators = new Operators(
            [
                Operators::CONTAINS,
                Operators::NOT_CONTAINS,
                Operators::NOT_IS_EMPTY,
                Operators::IS_EMPTY,
                Operators::EQ,
                Operators::NEQ,
                Operators::BEGINS_WITH,
                Operators::ENDS_WITH,
            ], false
        );

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        if (in_array($operator, [Operators::IS_EMPTY, Operators::NOT_IS_EMPTY], true)) {
            $sub_query = $wpdb->prepare(
                "
				SELECT DISTINCT cc.comment_post_ID
				FROM $wpdb->comments AS cc
					INNER JOIN $wpdb->commentmeta AS cm ON ( cc.comment_ID = cm.comment_id AND cm.meta_key = 'is_customer_note' AND cm.meta_value = '1' )
				WHERE 
					cc.comment_type = 'order_note' AND cc.comment_author != %s
			",
                __('WooCommerce', 'woocommerce')
            );

            $bindings->where(
                sprintf(
                    "{$wpdb->prefix}wc_orders.id %s ( $sub_query )",
                    Operators::IS_EMPTY === $operator ? 'NOT IN' : 'IN'
                )
            );

            return $bindings;
        }

        $alias = $bindings->get_unique_alias('notc');
        $alias_meta = $bindings->get_unique_alias('notc_meta');

        // Discard comments with 'WooCommerce' as author
        $join = $wpdb->prepare(
            "
				INNER JOIN $wpdb->comments AS $alias ON ( {$wpdb->prefix}wc_orders.id = $alias.comment_post_ID AND $alias.comment_type = 'order_note' AND $alias.comment_author != %s )
			",
            __('WooCommerce', 'woocommerce')
        );

        // A customer note has 'is_customer_note' as metadata
        $join .= "INNER JOIN $wpdb->commentmeta AS $alias_meta ON ( $alias.comment_ID = $alias_meta.comment_id AND $alias_meta.meta_key = 'is_customer_note' AND $alias_meta.meta_value = '1' )";

        $bindings->join($join)
                 ->group_by("{$wpdb->prefix}wc_orders.id");

        $comparison = ComparisonFactory::create(
            "$alias.comment_content",
            $operator,
            $value
        );

        $where[] = $comparison();

        $bindings->where(implode(' AND ', $where));

        return $bindings;
    }

}