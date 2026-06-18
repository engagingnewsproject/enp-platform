<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product_Variation;

class PriceFormatted extends ProductVariationMethod
{

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        $regular = $product_variation->get_regular_price();
        $price = $product_variation->get_price();

        if ( ! $price) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $formatted_price = wc_price($price);
        if ($price < $regular) {
            $formatted_price = sprintf('<del>%s</del> <ins>%s</ins>', wc_price($regular), $formatted_price);
        }

        return $value->with_value($formatted_price);
    }

}