<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Formatter;
use AC\Type\Value;
use WC_Product_Variation;

abstract class ProductVariationMethod implements Formatter
{

    abstract protected function get_product_variation_value(
        WC_Product_Variation $product_variation,
        Value $value
    ): Value;

    public function format(Value $value)
    {
        $product_variation = new WC_Product_Variation($value->get_id());

        return $this->get_product_variation_value($product_variation, $value);
    }

}