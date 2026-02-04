<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product_Variation;

class ShippingClass extends ProductVariationMethod
{

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        $shipping_class_id = $product_variation->get_shipping_class_id();

        $term = get_term_by('id', $shipping_class_id, 'product_shipping_class');

        if ( ! $term) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $icon = '';

        if (empty(wp_get_post_terms($value->get_id(), 'product_shipping_class'))) {
            $icon = ac_helper()->html->tooltip(
                '<span class="woocommerce-help-tip"></span>',
                __('Shipping Class managed by product', 'codepress-admin-columns')
            );
        }

        return $value->with_value(
            sprintf('%s %s', ac_helper()->taxonomy->get_term_display_name($term), $icon)
        );
    }

}