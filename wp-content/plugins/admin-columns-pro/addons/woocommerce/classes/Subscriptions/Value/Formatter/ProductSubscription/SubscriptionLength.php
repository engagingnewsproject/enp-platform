<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\ProductSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Product_Subscription;

class SubscriptionLength implements Formatter
{

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product_Subscription) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $length = $product->get_meta('_subscription_length');

        $ranges = (array)wcs_get_subscription_ranges($product->get_meta('_subscription_period'));

        if ( ! array_key_exists($length, $ranges)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(ucfirst($ranges[$length]));
    }

}