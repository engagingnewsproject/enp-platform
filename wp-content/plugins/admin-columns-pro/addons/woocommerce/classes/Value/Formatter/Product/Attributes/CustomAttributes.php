<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\Attributes;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Product;

class CustomAttributes implements Formatter
{

    private $attribute;

    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $product_attributes = $product->get_attributes();

        if ( ! array_key_exists($this->attribute, $product_attributes)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $attributes = $product_attributes[$this->attribute]->get_options();

        if (empty($attributes)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            ac_helper()->string->enumeration_list($attributes, 'and')
        );
    }

}