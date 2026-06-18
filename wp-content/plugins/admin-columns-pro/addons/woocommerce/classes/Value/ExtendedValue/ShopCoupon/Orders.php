<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\ShopCoupon;

use AC;
use AC\Column;
use AC\Helper\Date;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use Automattic\WooCommerce\Utilities\OrderUtil;
use DateTimeZone;
use WC_Order_Item_Product;

class Orders implements ExtendedValue
{

    private const NAME = 'coupons-orders';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $order_ids = $this->get_order_ids_by_coupon_id($id);

        if (empty($order_ids)) {
            return __('No items', 'codepress-admin-columns');
        }

        $items = [];

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);

            if ( ! $order) {
                continue;
            }

            $order_label = sprintf('#%s', $order->get_order_number());
            $edit = OrderUtil::get_order_admin_edit_url((int)$order->get_id());

            if ($edit) {
                $order_label = sprintf('<a href="%s">%s</a>', $edit, $order_label);
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
                'order'    => $order_label,
                'date'     => $date
                    ? (string)wp_date(
                        Date::create()->get_date_format(),
                        $date->getTimestamp(),
                        new DateTimeZone('UTC')
                    )
                    : '-',
                'products' => $products ?: '-',
                'quantity' => $quantity > 0 ? $quantity : '-',
                'total'    => $order->get_total() > 0 ? wc_price($order->get_total()) : '-',
            ];
        }

        $view = new AC\View([
            'title' => __('Orders', 'codepress-admin-columns'),
            'items' => $items,
            'total' => count($items),
        ]);

        return $view->set_template('modal-value/orders')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-nopadding -w-large');
    }

    private function get_order_ids_by_coupon_id($id): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wc_order_coupon_lookup';

        $sql = "
			SELECT DISTINCT(order_id)
			FROM {$table}
			WHERE coupon_id = %d
		";

        return $wpdb->get_col($wpdb->prepare($sql, $id));
    }

}