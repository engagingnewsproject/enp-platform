<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Coupon;

class UsedBy implements Formatter
{

    public function format(Value $value)
    {
        $user_ids = (new WC_Coupon($value->get_id()))->get_used_by();

        if (empty($user_ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            sprintf(
                _n(
                    __('%d customer', 'codepress-admin-columns'),
                    __('%d customers', 'codepress-admin-columns'),
                    count($user_ids),
                    'codepress-admin-columns'
                ),
                count($user_ids)
            )
        );
    }

}