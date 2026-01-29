<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;
use WC_Product_Grouped;

class RegularPrice extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $price = $product->get_regular_price();

        if ($product instanceof WC_Product_Grouped) {
            $price = $product->get_min_price();
        }

        if ($price <= 0) {
            throw new ValueNotFoundException(
                'Regular price is not available for this product. ID: ' . $value->get_id()
            );
        }

        if ($product instanceof WC_Product_Grouped && $product->get_max_price()) {
            $price .= '-' . $product->get_max_price();
        }

        return $value->with_value($price);
    }

}