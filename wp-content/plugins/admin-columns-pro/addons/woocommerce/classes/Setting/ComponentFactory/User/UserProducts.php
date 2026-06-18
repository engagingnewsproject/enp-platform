<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\User;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class UserProducts extends BaseComponentFactory
{

    public const NAME = 'user_products';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                'total'  => __('Total products purchased', 'codepress-admin-columns'),
                'unique' => __('Unique products purchased', 'codepress-admin-columns'),
            ]),
            $config->get(self::NAME, 'total')
        );
    }

}