<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class OrderType implements Formatter
{

    public function format(Value $value)
    {
        $type = $this->get_order_type($value->get_id());

        if ( ! $type) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($type);
    }

    private function get_order_type($order_id): ?string
    {
        if (wcs_order_contains_subscription($order_id, 'renewal')) {
            return __('Renewal Order', 'woocommerce-subscriptions');
        } elseif (wcs_order_contains_subscription($order_id, 'resubscribe')) {
            return __('Resubscribe Order', 'woocommerce-subscriptions');
        } elseif (wcs_order_contains_subscription($order_id, 'parent')) {
            return __('Parent Order', 'woocommerce-subscriptions');
        }

        return null;
    }

}