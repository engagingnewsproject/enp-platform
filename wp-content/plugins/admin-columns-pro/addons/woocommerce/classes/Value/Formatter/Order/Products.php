<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\ValueCollection;
use WC_Order_Item_Product;

class Products implements Formatter
{

    /**
     * Stores total quantity per product ID for the current order being formatted.
     * Keyed by product/variation ID; reset at the start of each format() call.
     *
     * @var array<int, int>
     */
    private static array $quantity_cache = [];

    public static function get_quantity(int $product_id): int
    {
        return self::$quantity_cache[$product_id] ?? 0;
    }

    public function format($value, $id = null): ValueCollection
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        self::$quantity_cache = [];

        $product_ids = [];

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product && $item->get_quantity() > 0) {
                $product_id = $item->get_variation_id() ?: $item->get_product_id();
                $product_ids[] = $product_id;

                self::$quantity_cache[$product_id] = (self::$quantity_cache[$product_id] ?? 0) + $item->get_quantity();
            }
        }

        $product_ids = array_unique($product_ids);

        if (count($product_ids) === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $product_ids);
    }

}