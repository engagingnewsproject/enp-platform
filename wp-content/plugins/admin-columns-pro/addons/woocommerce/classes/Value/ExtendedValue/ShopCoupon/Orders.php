<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\ShopCoupon;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;

class Orders implements ExtendedValue
{

    private const NAME = 'coupons-orders';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $values = [];
        foreach ($this->get_order_ids_by_coupon_id($id) as $order_id) {
            $order = wc_get_order($order_id);
            $values[] = ac_helper()->html->link($order->get_edit_order_url(), '#' . $order_id);
        }

        return implode(', ', $values);
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('');
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