<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use WC_Order;
use WC_Order_Item_Product;

class ProductDetails implements Formatter
{

    public function format($value, $id = null)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order instanceof WC_Order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $view = new AC\View([
            'items' => $this->get_product_items($order),
        ]);

        return $value->with_value($view->set_template('value/product-details')->render());
    }

    private function get_product_items(WC_Order $order): array
    {
        $items = [];

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product && $item->get_quantity() > 0) {
                $items[] = $item;
            }
        }

        return $items;
    }

}