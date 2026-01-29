<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ProductVariation;

use AC\Type\Value;
use WC_Product_Variation;

class HasStatus extends ProductVariationMethod
{

    private $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    protected function get_product_variation_value(WC_Product_Variation $product_variation, Value $value): Value
    {
        return $value->with_value($product_variation->get_status() == $this->status);
    }

}