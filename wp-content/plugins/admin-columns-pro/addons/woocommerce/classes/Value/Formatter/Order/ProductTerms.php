<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\TaxonomySlug;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\WC\Value\Formatter\Product;
use WP_Term;

class ProductTerms implements Formatter
{

    private $taxonomy_slug;

    public function __construct(TaxonomySlug $taxonomy_slug)
    {
        $this->taxonomy_slug = $taxonomy_slug;
    }

    public function format(Value $value)
    {
        $products = (new Products())->format($value);

        if ( ! $products || ! $products instanceof ValueCollection) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $terms = [];

        foreach ($products as $product) {
            $product_terms = (new Product\ProductTerms($this->taxonomy_slug))->format($product);
            foreach ($product_terms as $term_value) {
                $term = $term_value->get_value();

                if ($term instanceof WP_Term) {
                    $terms[$term->term_id] = $term;
                }
            }
        }

        $terms = ac_helper()->taxonomy->get_term_links($terms, 'product');

        if (empty($terms)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(ac_helper()->string->enumeration_list($terms, 'and'));
    }

}