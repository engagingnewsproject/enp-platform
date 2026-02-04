<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Type\Value;
use WC_Product;

// TODO remove
class Stock extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $stock = $product->get_stock_status();

        if ($product->managing_stock()) {
            $stock .= ', ' . $product->get_stock_quantity();
        }

        return $value->with_value(
            $stock
        );
    }

}