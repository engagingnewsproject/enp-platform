<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Coupon;

class ExpiryDate implements Formatter
{

    public function format(Value $value)
    {
        $date = (new WC_Coupon($value->get_id()))->get_date_expires();

        if ( ! $date) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($date->format('Y-m-d'));
    }

}