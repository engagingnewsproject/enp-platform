<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class LowStockThreshold extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $threshold_product = (int)$product->get_low_stock_amount();

        if ($threshold_product > 0) {
            return $value->with_value($threshold_product);
        }

        $threshold_global = (int)get_option('woocommerce_notify_low_stock_amount', 0);

        if ($threshold_global > 0) {
            return $value->with_value(
                ac_helper()->html->tooltip(
                    sprintf('<strong style="color:#ccc">%d</strong>', $threshold_global),
                    sprintf(__('Set gobally to %d', 'codepress-admin-columns'), $threshold_global)
                )
            );
        }

        throw ValueNotFoundException::from_id($value->get_id());
    }

}