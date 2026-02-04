<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class Reviews extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $review_count = $product->get_review_count();

        if ( ! $review_count) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $link = add_query_arg(
            [
                'product_id'  => $value->get_id(),
                'status'      => 'approved',
                'post_type'   => 'product',
                'page'        => 'product-reviews',
                'review_type' => 'review',
            ],
            get_admin_url(null, 'edit.php')
        );

        return $value->with_value(
            ac_helper()->html->link($link, (string)$review_count)
        );
    }

}