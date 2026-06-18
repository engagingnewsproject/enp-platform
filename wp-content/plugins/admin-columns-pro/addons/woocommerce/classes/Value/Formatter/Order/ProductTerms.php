<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\TaxonomySlug;
use AC\Type\Value;
use WC_Order_Item_Product;

class ProductTerms implements Formatter
{

    private TaxonomySlug $taxonomy;

    public function __construct(TaxonomySlug $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    private function get_product_ids(int $order_id): array
    {
        $order = wc_get_order($order_id);

        if ( ! $order) {
            return [];
        }

        $product_ids = [];

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product && $item->get_quantity() > 0) {
                $product_ids[] = $item->get_product_id();
            }
        }

        return array_unique($product_ids);
    }

    private function get_product_terms(int $product_id): array
    {
        $terms = get_the_terms($product_id, (string)$this->taxonomy);

        return $terms && ! is_wp_error($terms)
            ? $terms
            : [];
    }

    public function format(Value $value)
    {
        $terms = [];

        foreach ($this->get_product_ids($value->get_id()) as $product_id) {
            foreach ($this->get_product_terms($product_id) as $term) {
                // add term_id as key to prevent duplicate entries
                $terms[$term->term_id] = $term;
            }
        }

        uasort($terms, static fn($a, $b) => strnatcmp($a->name, $b->name));

        if ( ! $terms) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $wc_attribute_taxonomies = wc_get_attribute_taxonomy_names();

        $labels = [];

        foreach ($terms as $term) {
            $label = sanitize_term_field('name', $term->name, $term->term_id, $term->taxonomy, 'display');

            // WC taxonomy attributes can not be filtered on the orders list table, link tot taxonomy list table.
            if (in_array($term->taxonomy, $wc_attribute_taxonomies, true)) {
                $url = (string)new AC\Type\Url\ListTable\Taxonomy((string)$this->taxonomy, 'product');
            } else {
                $url = Helper\Taxonomy::create()->get_filter_by_term_url($term, 'product');
            }

            $labels[] = sprintf(
                '<a href="%s">%s</a>',
                $url,
                $label
            );
        }

        return $value->with_value(
            Helper\Strings::create()->enumeration_list($labels, 'and')
        );
    }

}