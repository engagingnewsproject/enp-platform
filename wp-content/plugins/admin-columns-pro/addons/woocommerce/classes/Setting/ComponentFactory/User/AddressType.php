<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\User;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class AddressType extends BaseComponentFactory
{

    public const NAME = 'address_type';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                'shipping' => __('Shipping', 'codepress-admin-columns'),
                'billing'  => __('Billing', 'codepress-admin-columns'),
            ]),
            $config->get(self::NAME, 'billing')
        );
    }

}