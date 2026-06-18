<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Product;

abstract class ProductMethod implements Formatter
{

    abstract protected function get_product_value(WC_Product $product, Value $value): Value;

    public function format(Value $value): Value
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $this->get_product_value($product, $value);
    }

}