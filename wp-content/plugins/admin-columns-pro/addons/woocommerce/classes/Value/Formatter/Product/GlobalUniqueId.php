<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Type\Value;
use WC_Product;

class GlobalUniqueId extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        return $value->with_value($product->get_global_unique_id());
    }

}