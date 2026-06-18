<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WC_Coupon;

class EmailRestrictionsCollection implements Formatter
{

    public function format(Value $value)
    {
        $restricted = (new WC_Coupon($value->get_id()))->get_email_restrictions();

        if (empty($restricted)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new ValueCollection($value->get_id(), []);

        foreach ($restricted as $email) {
            $collection->add(new Value($value->get_id(), $email));
        }

        return $collection;
    }

}