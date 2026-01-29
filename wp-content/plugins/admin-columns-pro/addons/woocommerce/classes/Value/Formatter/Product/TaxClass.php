<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use ACA\WC\Helper\Tax;
use WC_Product;

class TaxClass extends ProductMethod
{

    private $display;

    public function __construct(bool $display = true)
    {
        $this->display = $display;
    }

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $tax_class = $product->get_tax_class();

        if ( ! $tax_class) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ($this->display) {
            $classes = (new Tax())->get_tax_class_options();
            $tax_class = $classes[$tax_class] ?: $tax_class;
        }

        return $value->with_value(
            $tax_class
        );
    }

}