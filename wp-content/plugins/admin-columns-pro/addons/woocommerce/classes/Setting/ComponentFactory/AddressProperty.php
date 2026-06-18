<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class AddressProperty extends BaseComponentFactory
{

    public const NAME = 'address_property';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                ''           => __('Full Address', 'codepress-admin-columns'),
                'first_name' => __('First Name', 'woocommerce'),
                'last_name'  => __('Last Name', 'woocommerce'),
                'full_name'  => __('Full Name', 'codepress-admin-columns'),
                'company'    => __('Company', 'woocommerce'),
                'address_1'  => sprintf(__('Address line %s', 'codepress-admin-columns'), 1),
                'address_2'  => sprintf(__('Address line %s', 'codepress-admin-columns'), 2),
                'city'       => __('City', 'woocommerce'),
                'postcode'   => __('Postcode', 'woocommerce'),
                'country'    => __('Country', 'woocommerce'),
                'state'      => __('State', 'woocommerce'),
                'email'      => __('Email', 'woocommerce'),
                'phone'      => __('Phone', 'woocommerce'),
            ]),
            $config->get(self::NAME, ''),
            null,
            false,
            new AttributeCollection([
                AttributeFactory::create_refresh(),
            ])
        );
    }

}