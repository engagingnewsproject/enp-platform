<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WC_Coupon;

class ExcludedProductCategoriesCollection implements Formatter
{

    public function format(Value $value)
    {
        $excluded = (new WC_Coupon($value->get_id()))->get_excluded_product_categories();

        if (empty($excluded)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $excluded);
    }

}