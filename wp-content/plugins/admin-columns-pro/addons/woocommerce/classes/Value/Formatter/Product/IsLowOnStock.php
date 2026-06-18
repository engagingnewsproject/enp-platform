<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class IsLowOnStock extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $is_managing_stock = $product->managing_stock();

        if ( ! $is_managing_stock) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $threshold_product = (int)$product->get_low_stock_amount();
        $threshold_global = (int)get_option('woocommerce_notify_low_stock_amount', 0);

        if ($threshold_product <= 0 && $threshold_global <= 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $stock = (int)$product->get_stock_quantity();

        $threshold = $threshold_product ?: $threshold_global;

        return $value->with_value($stock <= $threshold);
    }

}