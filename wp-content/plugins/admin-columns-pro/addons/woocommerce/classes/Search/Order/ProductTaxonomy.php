<?php

declare(strict_types=1);

namespace ACA\WC\Search\Order;

use AC\Helper\Select\Options\Paginated;
use ACA\WC\Search;
use ACP;
use ACP\Helper\Select\Taxonomy\LabelFormatter\TermName;
use ACP\Helper\Select\Taxonomy\PaginatedFactory;
use ACP\Query\Bindings;
use ACP\Search\Operators;
use ACP\Search\Value;
use WP_Term;

class ProductTaxonomy extends ACP\Search\Comparison implements ACP\Search\Comparison\SearchableValues
{

    private string $taxonomy;

    public function __construct(string $taxonomy)
    {
        parent::__construct(
            new Operators([
                Operators::EQ,
            ]),
            Value::DECIMAL
        );

        $this->taxonomy = $taxonomy;
    }

    public function format_label($value): string
    {
        $term = get_term($value);

        return $term instanceof WP_Term
            ? (new TermName())->format_label($term)
            : '';
    }

    public function get_values(string $search, int $page): Paginated
    {
        return (new PaginatedFactory())->create([
            'search'   => $search,
            'page'     => $page,
            'taxonomy' => $this->taxonomy,
        ]);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings\QueryArguments();
        $alias_items = $bindings->get_unique_alias('product_taxonomy');
        $alias_itemmeta = $bindings->get_unique_alias('product_taxonomy');
        $alias_termrelation = $bindings->get_unique_alias('product_taxonomy');
        $alias_termtax = $bindings->get_unique_alias('product_taxonomy');

        $bindings->join(
            "
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS $alias_items ON {$wpdb->prefix}wc_orders.id = $alias_items.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS $alias_itemmeta ON $alias_items.order_item_id = $alias_itemmeta.order_item_id
            INNER JOIN {$wpdb->prefix}term_relationships AS $alias_termrelation ON CAST($alias_itemmeta.meta_value AS UNSIGNED) = $alias_termrelation.object_id
            INNER JOIN {$wpdb->prefix}term_taxonomy AS $alias_termtax ON $alias_termrelation.term_taxonomy_id = $alias_termtax.term_taxonomy_id
            "
        );
        $bindings->where(
            $wpdb->prepare(
                "
                $alias_itemmeta.meta_key = '_product_id' AND
                $alias_termtax.taxonomy = %s AND
                $alias_termtax.term_id = %d
                ",
                $this->taxonomy,
                (int)$value->get_value()
            )
        );

        return $bindings;
    }

}