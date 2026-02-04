<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Type\AddressType;

class WcFormattedState implements Formatter
{

    private AddressType $address_type;

    public function __construct(AddressType $address_type)
    {
        $this->address_type = $address_type;
    }

    public function format(Value $value)
    {
        $state = $value->get_value();
        $order = wc_get_order($value->get_id());
        $country = $this->address_type->get() === 'billing'
            ? $order->get_billing_country()
            : $order->get_shipping_country();
        $countries = WC()->countries->get_states($country);

        $formatted_state = $countries[$state] ?? $state;

        return $value->with_value($formatted_state);
    }

}