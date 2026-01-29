<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WC_Coupon;

class ProductCategoriesCollection implements Formatter
{

    public function format(Value $value)
    {
        $included = (new WC_Coupon($value->get_id()))->get_product_categories();

        if (empty($included)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $included);
    }

}