<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\ShopOrder;

use ACA\WC\Type\AddressType;
use ACP;
use ACP\Sorting\Model\QueryBindings;

final class AddressFactory
{

    public function create(string $address_property, AddressType $address_type): ?QueryBindings
    {
        if (in_array($address_property, ['full_name', ''])) {
            return null;
        }

        $meta_key_prefix = $address_type->get() === AddressType::BILLING
            ? '_billing_' : '_shipping_';

        $meta_key = $meta_key_prefix . $address_property;

        return new ACP\Sorting\Model\Post\Meta($meta_key);
    }

}