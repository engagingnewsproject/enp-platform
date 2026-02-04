<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Address;

use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Type\AddressType;

class ShippingAddressFactory extends AddressFactory
{

    protected function get_address_type(): AddressType
    {
        return new AddressType(AddressType::SHIPPING);
    }

    public function get_label(): string
    {
        return __('Shipping Address', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_shipping_address';
    }

}