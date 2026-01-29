<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Type\Value;
use WC_Product;

class ProductType extends ProductMethod
{

    private $simple_types;

    public function __construct(array $simple_types)
    {
        $this->simple_types = $simple_types;
    }

    private function is_simple_product_type(string $product_type): bool
    {
        return in_array($product_type, $this->simple_types, true);
    }

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $product_type = $product->get_type();

        $label = $this->get_product_type_label($product_type);

        if ($this->is_simple_product_type($product_type)) {
            $additional = [];

            if ($product->is_downloadable()) {
                $additional[] = __('Downloadable', 'woocommerce');
            }

            if ($product->is_virtual()) {
                $additional[] = __('Virtual', 'woocommerce');
            }

            if ($additional) {
                $label .= sprintf(' (%s)', implode(' &amp; ', $additional));
            }
        }

        return $value->with_value($label);
    }

    private function get_product_type_label($product_type)
    {
        $types = wc_get_product_types();

        if ( ! isset($types[$product_type])) {
            return false;
        }

        return $types[$product_type];
    }

}