<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use ACA\WC\Type\ProductAttribute;
use WC_Product;

class DefaultFormValues extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ($product->get_type() !== 'variable') {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $default_attributes = $product->get_default_attributes();

        if (empty($default_attributes)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $result = [];

        foreach ($default_attributes as $key => $default_value) {
            $result[] = sprintf(
                '<strong>%s:</strong> %s',
                (new ProductAttribute($key))->get_label(),
                $default_value
            );
        }

        return $value->with_value(
            implode(', ', $result)
        );
    }

}