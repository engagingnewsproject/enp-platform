<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Formatter;
use AC\Type\Value;
use WC_Coupon;

class FreeShipping implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value((new WC_Coupon($value->get_id()))->get_free_shipping());
    }

}