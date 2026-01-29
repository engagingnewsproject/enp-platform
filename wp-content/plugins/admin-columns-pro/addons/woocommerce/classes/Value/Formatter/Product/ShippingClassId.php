<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class ShippingClassId extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $shipping_class_id = $product->get_shipping_class_id();

        if ( ! $shipping_class_id) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $term = get_term($shipping_class_id);

        if ( ! $term) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return new Value($shipping_class_id, $shipping_class_id);
    }

}