<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use ACA\WC\Type\ProductAttribute;
use WC_Product_Variation;

class VariationAttribute extends ProductVariationMethod
{

    private $attribute;

    public function __construct(ProductAttribute $attribute)
    {
        $this->attribute = $attribute;
    }

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        foreach ($product_variation->get_attributes() as $name => $label) {
            if ($this->attribute->get_name() !== $name) {
                continue;
            }

            $label = $this->attribute->is_taxonomy()
                ? $product_variation->get_attribute($this->attribute->get_name())
                : $label;

            return $value->with_value($label);
        }

        throw ValueNotFoundException::from_id($value->get_id());
    }

}