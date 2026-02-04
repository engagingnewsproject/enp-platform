<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Post\RelatedMeta\Taxonomy;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;

class TermField implements QueryBindings
{

    private string $term_field;

    private string $meta_key;

    public function __construct(string $term_field, string $meta_key)
    {
        $this->term_field = $term_field;
        $this->meta_key = $meta_key;
    }

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $term_alias = $bindings->get_unique_alias('term');
        $postmeta_alias = $bindings->get_unique_alias('postmeta');

        $join = $wpdb->prepare(
            "
			LEFT JOIN $wpdb->postmeta AS $postmeta_alias ON $wpdb->posts.ID = $postmeta_alias.post_id AND $postmeta_alias.meta_key = %s
			LEFT JOIN $wpdb->terms AS $term_alias ON $term_alias.term_id = $postmeta_alias.meta_value
			",
            $this->meta_key
        );

        $bindings->join($join);
        $bindings->group_by("$wpdb->posts.ID");
        $bindings->order_by(
            SqlOrderByFactory::create(
                "$term_alias.$this->term_field",
                (string)$order
            )
        );

        return $bindings;
    }

}