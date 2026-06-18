<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class ShippingMethodType extends BaseComponentFactory
{

    public const NAME = 'shipping_method_type';
    public const METHOD_TITLE = 'method_title';
    public const METHOD_ID = 'method_id';

    protected function get_label(Config $config): ?string
    {
        return __('Shipping Method Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                self::METHOD_TITLE => __('Method Label', 'codepress-admin-columns'),
                self::METHOD_ID    => __('Method Type', 'codepress-admin-columns'),
            ]),
            $config->get(self::NAME, self::METHOD_TITLE)
        );
    }

}