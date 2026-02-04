<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Formatter;
use AC\Type\Value;
use WC_Coupon;

class Type implements Formatter
{

    public function format(Value $value)
    {
        $coupon = new WC_Coupon($value->get_id());
        $type = $coupon->get_discount_type();

        $coupon_type = $type
            ? wc_get_coupon_type($type)
            : '';

        return $value->with_value($coupon_type);
    }

}