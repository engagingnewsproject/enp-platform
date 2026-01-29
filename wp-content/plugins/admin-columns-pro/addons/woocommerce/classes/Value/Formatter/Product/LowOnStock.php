<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class LowOnStock extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $is_managing_stock = $product->managing_stock();

        if ( ! $is_managing_stock) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $threshold_product = (int)$product->get_low_stock_amount();
        $threshold_global = (int)get_option('woocommerce_notify_low_stock_amount', 0);
        $has_threshold = $threshold_product > 0 || $threshold_global > 0;

        if ( ! $has_threshold) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $threshold = $threshold_product ?: $threshold_global;
        $stock = (int)$product->get_stock_quantity();

        $label = sprintf(
            '<strong style="color: #eaa600">%s</strong> (%d)',
            __('Low On Stock', 'codepress-admin-columns'),
            $product->get_stock_quantity()
        );

        if ($stock <= $threshold) {
            return $value->with_value(
                ac_helper()->html->tooltip(
                    $label,
                    sprintf(
                        __('Current stock (%d) is below threshold (%d).', 'codepress-admnin-columns'),
                        $stock,
                        $threshold
                    )
                )
            );
        }

        throw ValueNotFoundException::from_id($value->get_id());
    }

}