<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Type\AddressType;
use WC_Order;

class OrderAddress implements Formatter
{

    private $type;

    private $property;

    public function __construct(AddressType $type, string $property)
    {
        $this->property = $property;
        $this->type = $type;
    }

    public function format(Value $value)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order instanceof WC_Order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $method = $this->get_address_method();

        if ( ! method_exists($order, $method)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $address = $order->$method();

        if ( ! $address) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($address);
    }

    private function get_address_method(): string
    {
        $mapping = [
            'address_1'  => 'get_%s_address_1',
            'address_2'  => 'get_%s_address_2',
            'city'       => 'get_%s_city',
            'company'    => 'get_%s_company',
            'country'    => 'get_%s_country',
            'first_name' => 'get_%s_first_name',
            'last_name'  => 'get_%s_last_name',
            'full_name'  => 'get_formatted_%s_full_name',
            'postcode'   => 'get_%s_postcode',
            'state'      => 'get_%s_state',
            'email'      => 'get_%s_email',
            'phone'      => 'get_%s_phone',
        ];

        $method = $mapping[$this->property] ?? 'get_formatted_%s_address';

        return sprintf($method, $this->type);
    }

}