<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Type\AddressType;

class Address implements Formatter
{

    private string $property;

    private AddressType $address_type;

    public function __construct(AddressType $address_type, string $property)
    {
        $this->property = $property;
        $this->address_type = $address_type;
    }

    public function format(Value $value)
    {
        if ($this->property === '') {
            return $value->with_value(
                wc_get_account_formatted_address((string)$this->address_type, $value->get_id())
            );
        }

        if ($this->is_meta_key($this->property)) {
            $meta_key = sprintf('%s_%s', $this->address_type, $this->property);

            return $value->with_value(get_user_meta($value->get_id(), $meta_key, true));
        }

        throw ValueNotFoundException::from_id($value->get_id());
    }

    private function is_meta_key(string $property): bool
    {
        $valid_meta_keys = [
            'first_name',
            'last_name',
            'full_name',
            'company',
            'address_1',
            'address_2',
            'city',
            'postcode',
            'country',
            'state',
            'email',
            'phone',
        ];

        return in_array($property, $valid_meta_keys, true);
    }

}