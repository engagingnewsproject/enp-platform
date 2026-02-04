<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\User;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;
use WC_Order_Item_Product;

class Orders implements ExtendedValue
{

    public function can_render(string $view): bool
    {
        return $view === 'user-orders';
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $order_status = $params['order_status'] ?? [];

        $orders = $this->get_orders($id, $order_status, 50);

        if (count($orders) < 1) {
            return __('No items', 'codepress-admin-columns');
        }

        $items = [];

        foreach ($orders as $order) {
            $order_id = sprintf('#%s', $order->get_order_number());

            $edit = OrderUtil::get_order_admin_edit_url((int)$order->get_id());

            if ($edit) {
                $order_id = sprintf('<a href="%s">%s</a>', $edit, $order_id);
            }

            $products = $quantity = 0;

            foreach ($order->get_items() as $item) {
                if ($item instanceof WC_Order_Item_Product && $item->get_quantity() > 0) {
                    $products++;
                    $quantity += $item->get_quantity();
                }
            }

            $date = $order->get_date_completed() ?: $order->get_date_created();

            $items[] = [
                'order'    => $order_id,
                'date'     => ac_helper()->date->date_by_timestamp($date->getTimestamp()),
                'products' => $products ?: '-',
                'quantity' => $quantity > 0 ? $quantity : '-',
                'total'    => $order->get_total() > 0 ? wc_price($order->get_total()) : '-',
            ];
        }

        $view = new AC\View([
            'title' => __('Recent orders', 'codepress-admin-columns'),
            'items' => $items,
            'total' => count($items),
        ]);

        return $view->set_template('modal-value/orders')->render();
    }

    /**
     * @return WC_Order[]
     */
    private function get_orders(int $user_id, array $order_status, ?int $limit = null): array
    {
        $args = [
            'customer_id' => $user_id,
            'limit'       => $limit ?? -1,
        ];

        if ($order_status) {
            $args['status'] = $order_status;
        }

        return wc_get_orders($args);
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, 'user-orders'))
            ->with_class('-nopadding -w-large');
    }

}