<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class Period extends BaseComponentFactory
{

    public const NAME = 'period';

    protected function get_label(Config $config): ?string
    {
        return __('Period', 'codepress-admin-columns');
    }

    protected function get_description(Config $config): ?string
    {
        return __('Select the time period from now', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                '365' => __('Last year', 'codepress-admin-columns'),
                '92'  => __('Last quarter', 'codepress-admin-columns'),
                '31'  => __('Last month', 'codepress-admin-columns'),
                '7'   => __('Last week', 'codepress-admin-columns'),
            ]),
            (string)$config->get(self::NAME, '365')
        );
    }

}