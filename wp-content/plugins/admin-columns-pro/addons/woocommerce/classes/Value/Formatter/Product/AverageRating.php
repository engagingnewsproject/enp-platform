<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class AverageRating extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $rating = $product->get_average_rating();

        if ( ! $rating) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($rating);
    }

}