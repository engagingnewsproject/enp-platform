<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Address;

use ACA\WC\Type\AddressType;

class ShippingAddress extends Address
{

    public function get_column_type(): string
    {
        return 'column-wc-order_shipping_address';
    }

    public function get_label(): string
    {
        return __('Shipping Address', 'codepress-admin-columns');
    }

    protected function get_address_type(): AddressType
    {
        return new AddressType('shipping');
    }
}