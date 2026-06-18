<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class StockValue extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ($product->is_type('variable') && ! $product->managing_stock()) {
            $total = $this->get_variable_stock_value($product);
        } else {
            $stock = $product->get_stock_quantity();
            $price = (float)$product->get_price();

            if ($stock === null || $stock <= 0 || $price <= 0) {
                throw ValueNotFoundException::from_id($value->get_id());
            }

            $total = $price * $stock;
        }

        if ($total <= 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($total);
    }

    private function get_variable_stock_value(WC_Product $product): float
    {
        $total = 0.0;

        foreach ($product->get_children() as $child_id) {
            $variation = wc_get_product($child_id);

            if ( ! $variation instanceof WC_Product || ! $variation->managing_stock()) {
                continue;
            }

            $stock = (int)$variation->get_stock_quantity();
            $price = (float)$variation->get_price();

            if ($stock > 0 && $price > 0) {
                $total += $price * $stock;
            }
        }

        return $total;
    }

}
