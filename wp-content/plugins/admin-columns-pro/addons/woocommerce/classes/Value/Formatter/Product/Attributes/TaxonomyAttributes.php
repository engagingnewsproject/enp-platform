<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\Attributes;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\TaxonomySlug;
use AC\Type\Value;
use WC_Product;

class TaxonomyAttributes implements Formatter
{

    private TaxonomySlug $taxonomy_slug;

    public function __construct(TaxonomySlug $taxonomy_slug)
    {
        $this->taxonomy_slug = $taxonomy_slug;
    }

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $attributes = wc_get_product_terms($product->get_id(), (string)$this->taxonomy_slug, ['fields' => 'names']);

        if (empty($attributes)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            Helper\Strings::create()->enumeration_list(
                $attributes,
                'and'
            )
        );
    }

}