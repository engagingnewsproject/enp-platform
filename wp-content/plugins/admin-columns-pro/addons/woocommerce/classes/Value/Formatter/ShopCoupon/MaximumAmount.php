<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Coupon;

class MaximumAmount implements Formatter
{

    public function format(Value $value)
    {
        $amount = (new WC_Coupon($value->get_id()))->get_maximum_amount();

        if ( ! $amount) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($amount);
    }

}