<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Type\Value;
use WC_Product;

class StockQuantity extends ProductMethod
{

    private bool $add_dashicon;

    public function __construct(bool $add_dashicon = true)
    {
        $this->add_dashicon = $add_dashicon;
    }

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        if ($product->is_type('variable') && ! $product->managing_stock()) {
            $quantity = $this->get_total_variation_amount($product);

            if ($this->add_dashicon) {
                $quantity .= ' ' . ac_helper()->icon->dashicon([
                        'icon'    => 'info-outline',
                        'tooltip' => 'from variations',
                    ]);
            }
        } else {
            $quantity = $product->get_stock_quantity();
        }

        return $value->with_value(
            $quantity
        );
    }

    private function get_total_variation_amount(WC_Product $product): int
    {
        $total_stock = 0;

        foreach ($product->get_children() as $child_id) {
            $variation = wc_get_product($child_id);

            if ($variation->managing_stock()) {
                $total_stock += max(0, (int)$variation->get_stock_quantity());
            }
        }

        return $total_stock;
    }

}