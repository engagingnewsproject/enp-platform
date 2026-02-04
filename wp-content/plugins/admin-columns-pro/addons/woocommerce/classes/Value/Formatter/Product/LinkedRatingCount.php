<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class LinkedRatingCount extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $rating_count = $product->get_rating_count();

        if ( ! $rating_count) {
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
        $count = ac_helper()->html->link($link, (string)$rating_count);

        return $value->with_value('(' . $count . ')');
    }

}