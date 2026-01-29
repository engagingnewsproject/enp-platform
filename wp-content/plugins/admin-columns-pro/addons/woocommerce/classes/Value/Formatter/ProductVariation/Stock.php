<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Type\Value;
use WC_Product_Variation;

class Stock extends ProductVariationMethod
{

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        $label = 'instock' === $product_variation->get_stock_status()
            ? __('In stock', 'woocommerce')
            : __('Out of stock', 'woocommerce');

        $quantity = $product_variation->get_stock_quantity() ?: '';
        $icon = '';

        if ('parent' === $product_variation->get_manage_stock()) {
            $icon = ac_helper()->html->tooltip(
                '<span class="woocommerce-help-tip"></span>',
                __('Stock managed by product', 'codepress-admin-columns')
            );
        }

        return $value->with_value(
            sprintf(
                '<mark class="%s">%s</mark> %s %s',
                $product_variation->get_stock_status(),
                $label,
                $quantity,
                $icon
            )
        );
    }

}