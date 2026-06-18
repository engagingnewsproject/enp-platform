<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use stdClass;
use WC_Product;
use WC_Product_Attribute;

trait VariationDisplayTrait
{

    protected function get_attribute_label(WC_Product_Attribute $attribute): string
    {
        $label = $attribute->get_name();

        if ($attribute->is_taxonomy()) {
            /** @var stdClass $taxonomy */
            $taxonomy = $attribute->get_taxonomy_object();
            $label = $taxonomy->attribute_label;
        }

        return $label;
    }

    protected function get_product_attribute(WC_Product $product, string $attribute_name): ?WC_Product_Attribute
    {
        $product_attributes = $product->get_attributes();

        return $product_attributes[$attribute_name] ?: null;
    }

}