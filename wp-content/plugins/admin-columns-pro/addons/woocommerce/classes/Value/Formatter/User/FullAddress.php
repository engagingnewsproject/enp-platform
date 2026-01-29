<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Type\AddressType;

class FullAddress implements Formatter
{

    private $address_type;

    public function __construct(AddressType $address_type)
    {
        $this->address_type = $address_type;
    }

    public function format(Value $value)
    {
        return $value->with_value(
            wc_get_account_formatted_address((string)$this->address_type, $value->get_id())
        );
    }

}