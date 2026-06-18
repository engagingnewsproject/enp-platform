<?php

declare(strict_types=1);

namespace ACA\WC\Editing\ShopOrder;

use ACA\WC\Type\AddressType;
use ACP;

class AddressFactory
{

    public function create(string $address_property, AddressType $address_type): ?ACP\Editing\Service
    {
        switch ($address_property) {
            case '' :
            case 'full_name' :
                return null;

            case 'country' :
                $options = array_merge(['' => __('None', 'codepress-admin-columns')], WC()->countries->get_countries());

                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\Select($options),
                    new ACP\Editing\Storage\Post\Meta($this->get_meta_key($address_type, $address_property))
                );

            default :
                return new ACP\Editing\Service\Basic(
                    (new ACP\Editing\View\Text())->set_clear_button(true),
                    new ACP\Editing\Storage\Post\Meta($this->get_meta_key($address_type, $address_property))
                );
        }
    }

    private function get_meta_key(AddressType $address_type, string $address_property): string
    {
        return $address_type->get() === AddressType::BILLING
            ? '_billing_' . $address_property : '_shipping_' . $address_property;
    }

}