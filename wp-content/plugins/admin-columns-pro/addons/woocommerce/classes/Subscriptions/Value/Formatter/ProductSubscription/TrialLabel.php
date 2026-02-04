<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\ProductSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Product_Subscription;

class TrialLabel implements Formatter
{

    public function format(Value $value)
    {
        $product = wc_get_product($value->get_id());

        if ( ! $product instanceof WC_Product_Subscription) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $length = (int)$product->get_meta('_subscription_trial_length');

        if ($length < 1) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $period = $product->get_meta('_subscription_trial_period');
        $periods = wcs_get_available_time_periods(1 === $length ? 'singular' : 'plural');

        if ( ! array_key_exists($period, $periods)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(sprintf('%d %s', $length, $periods[$period]));
    }

}