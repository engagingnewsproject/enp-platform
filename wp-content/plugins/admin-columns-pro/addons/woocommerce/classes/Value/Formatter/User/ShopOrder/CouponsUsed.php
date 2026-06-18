<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User\ShopOrder;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Helper;

class CouponsUsed implements Formatter
{

    public function format(Value $value)
    {
        $coupons = [];

        foreach ($this->get_orders_by_user($value->get_id()) as $order) {
            foreach ($order->get_coupon_codes() as $coupon) {
                $coupons[] = AC\Helper\Html::create()->link(
                    get_edit_post_link($order->get_id()),
                    $coupon,
                    ['tooltip' => 'order: #' . $order->get_id()]
                );
            }
        }

        if (empty($coupons)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(implode(' | ', $coupons));
    }

    private function get_orders_by_user($user_id): array
    {
        return Helper\User::create()->get_shop_orders_by_user((int)$user_id);
    }

}