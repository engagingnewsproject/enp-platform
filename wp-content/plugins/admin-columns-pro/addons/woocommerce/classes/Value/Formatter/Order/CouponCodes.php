<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Coupon;
use WC_Order;

class CouponCodes extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $coupons = $order->get_coupon_codes();

        if (empty($coupons)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $used_coupons = [];

        foreach ($coupons as $code) {
            $coupon = new WC_Coupon($code);
            $used_coupons[] = ac_helper()->html->link(get_edit_post_link($coupon->get_id()), $code);
        }

        return $value->with_value(implode(' | ', $used_coupons));
    }

}