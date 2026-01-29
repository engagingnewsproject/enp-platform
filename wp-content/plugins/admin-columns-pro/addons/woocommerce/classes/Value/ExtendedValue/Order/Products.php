<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\Order;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use WC_Order_Item_Product;

class Products implements ExtendedValue
{

    private const NAME = 'order-purchased';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $order = wc_get_order($id);

        if ( ! $order) {
            return __('No order found', 'codepress-admin-columns');
        }

        $order_items = $order->get_items();

        if (empty($order_items)) {
            return __('No products found', 'codepress-admin-columns');
        }

        $view = new AC\View([
            'items' => $this->get_ordered_items($order_items),
        ]);

        return $view->set_template('modal-value/purchased')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))->with_class('-nopadding -w-large');
    }

    private function get_ordered_items(array $order_items): array
    {
        $items = [];

        $sku_enabled = wc_product_sku_enabled();

        foreach ($order_items as $item) {
            if ( ! $item instanceof WC_Order_Item_Product) {
                continue;
            }

            $sku = null;
            $name = $item->get_name();
            $qty = $item->get_quantity();
            $total = $item->get_total();
            $tax = $item->get_total_tax();

            $product = $item->get_product();

            if ($product && 'trash' !== $product->get_status()) {
                $link = get_edit_post_link($product->get_id());

                if ($link) {
                    $name = sprintf('<a href="%s">%s</a>', $link, $name);
                }

                if ($sku_enabled) {
                    $sku = $product->get_sku();
                }
            }

            $meta = [];

            foreach ($item->get_formatted_meta_data() as $meta_item) {
                $meta[] = sprintf('<strong>%s:</strong> %s', $meta_item->display_key, $meta_item->value);
            }

            $items[] = [
                'qty'   => $qty > 0 ? sprintf('× %s', $qty) : '-',
                'name'  => $name,
                'sku'   => $sku,
                'tax'   => $tax > 0 ? wc_price($tax) : '-',
                'total' => $total > 0 ? wc_price($total) : '-',
                'meta'  => implode('<br>', $meta),
            ];
        }

        return $items;
    }

}