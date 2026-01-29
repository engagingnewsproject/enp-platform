<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Type\Value;
use WC_Product_Variation;

class VariationShort extends ProductVariationMethod
{

    use VariationDisplayTrait;

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        $items = [];

        foreach ($product_variation->get_attributes() as $attribute_name => $attribute_value) {
            $items[] = ac_helper()->html->tooltip(
                urldecode($attribute_value),
                $this->get_attribute_label_by_variation($product_variation, $attribute_name)
            );
        }

        return $value->with_value(implode(' | ', array_filter($items)));
    }

    private function get_attribute_label_by_variation(WC_Product_Variation $variation, string $attribute_name): string
    {
        $attribute = $this->get_product_attribute(wc_get_product($variation->get_parent_id()), $attribute_name);

        return $attribute ? $this->get_attribute_label($attribute) : $attribute_name;
    }

}