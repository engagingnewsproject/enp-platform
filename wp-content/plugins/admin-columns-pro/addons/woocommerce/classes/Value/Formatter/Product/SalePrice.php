<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class SalePrice extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ( ! $product->is_on_sale()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $price = $product->get_sale_price();

        if ($price < 0) {
            throw new ValueNotFoundException(
                'Sale price can not be less than zero. ID: ' . $value->get_id()
            );
        }

        return $value->with_value($price);
    }

}