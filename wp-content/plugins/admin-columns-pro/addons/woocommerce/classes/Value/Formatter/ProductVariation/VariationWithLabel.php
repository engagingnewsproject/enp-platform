<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Type\Value;
use WC_Product_Variation;

class VariationWithLabel extends ProductVariationMethod
{

    use VariationDisplayTrait;

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        $product = wc_get_product($product_variation->get_parent_id());
        $labels = [];

        foreach ($product_variation->get_attributes() as $attribute_name => $attribute_value) {
            $attribute = $this->get_product_attribute($product, $attribute_name);

            if ( ! $attribute) {
                continue;
            }

            $label = $this->get_attribute_label($attribute);

            if ($attribute_value && $attribute->is_taxonomy()) {
                $term = get_term_by('slug', $attribute_value, $attribute->get_taxonomy());

                if ($term) {
                    $attribute_value = $term->name;
                }
            }

            if ( ! $attribute_value) {
                $attribute_value = __('Any', 'codepress-admin-columns');
            }

            $labels[] = sprintf('<strong>%s</strong>: %s', $label, $attribute_value);
        }

        return $value->with_value(
            implode('<br>', array_filter($labels))
        );
    }

}