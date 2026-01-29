<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class TaxStatus extends ProductMethod
{

    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $tax_status = $product->get_tax_status();

        if ( ! $tax_status) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            $this->mapping[$tax_status] ?? $tax_status
        );
    }

}