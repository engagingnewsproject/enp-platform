<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\Variation;

use AC\Formatter;
use AC\Type\Value;
use WC_Product_Variation;

class VariationName implements Formatter
{

    private function get_variation(Value $value): ?WC_Product_Variation
    {
        if ($value->get_value() instanceof WC_Product_Variation) {
            return $value->get_value();
        }

        $product = wc_get_product($value->get_id());

        return $product instanceof WC_Product_Variation
            ? $product
            : null;
    }

    public function format(Value $value)
    {
        $variation = $this->get_variation($value);

        return $value->with_value($variation ? $variation->get_name() : '');
    }

}