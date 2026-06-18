<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class Dimensions extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ($product->is_virtual()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $dimensions = $product->get_dimensions(false);
        $values = [];

        foreach (['length', 'width', 'height'] as $d) {
            if ( ! empty($dimensions[$d])) {
                $label = $this->get_dimension_label($d);
                $values[$label] = $dimensions[$d];
            }
        }

        if (empty($values)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            implode(' x ', $values) . ' ' . get_option('woocommerce_dimension_unit')
        );
    }

    private function get_dimension_label($dimension): ?string
    {
        $labels = [
            'length' => __('Length', 'codepress-admin-columns'),
            'width'  => __('Width', 'codepress-admin-columns'),
            'height' => __('Height', 'codepress-admin-columns'),
        ];

        return $labels[$dimension] ?? null;
    }

}