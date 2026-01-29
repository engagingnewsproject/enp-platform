<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class CouponLimit extends BaseComponentFactory
{

    public const NAME = 'coupon_limit';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                'usage_limit'          => __('Usage limit per coupon', 'woocommerce'),
                'usage_limit_per_user' => __('Usage limit per user', 'woocommerce'),
            ]),
            $config->get(self::NAME, 'usage_limit')
        );
    }

}