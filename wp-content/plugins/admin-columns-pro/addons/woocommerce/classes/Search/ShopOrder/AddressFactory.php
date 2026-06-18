<?php

declare(strict_types=1);

namespace ACA\WC\Search\ShopOrder;

use ACA\WC\Search\ShopOrder\Address\Country;
use ACA\WC\Type\AddressType;
use ACP;

class AddressFactory
{

    public function create(
        string $address_property,
        AddressType $address_type
    ): ?ACP\Search\Comparison {
        $meta_key_prefix = $address_type->get() === AddressType::BILLING
            ? '_billing_' : '_shipping_';

        $meta_key = $meta_key_prefix . $address_property;

        switch ($address_property) {
            case '' :
            case 'full_name' :
                return null;
            case 'country' :
                return new Country($meta_key);
            default :
                return new ACP\Search\Comparison\Meta\Text($meta_key);
        }
    }

}