<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product_Variation;

class Sku extends ProductVariationMethod
{

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        $sku = $product_variation->get_sku();

        if ( ! $sku) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $data = $product_variation->get_data();

        if (empty($data['sku'])) {
            $sku .= ac_helper()->html->tooltip(
                '<span class="woocommerce-help-tip"></span>',
                __('SKU from product', 'codepress-admin-columns')
            );
        }

        return $value->with_value($sku);
    }

}