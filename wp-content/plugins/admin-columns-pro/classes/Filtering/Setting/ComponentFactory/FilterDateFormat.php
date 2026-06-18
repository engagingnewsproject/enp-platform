<?php

namespace ACP\Filtering\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class FilterDateFormat extends BaseComponentFactory
{

    private const NAME = 'filter_format';

    protected function get_label(Config $config): ?string
    {
        return __('Filter by', 'codepress-admin-columns');
    }

    protected function get_description(Config $config): ?string
    {
        return __('This will allow you to set the filter format.', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array([
                ''            => __('Daily', 'codepress-admin-columns'),
                'monthly'     => __('Monthly', 'codepress-admin-columns'),
                'yearly'      => __('Yearly', 'codepress-admin-columns'),
                'future_past' => __('Future / Past', 'codepress-admin-columns'),
                'range'       => __('Range', 'codepress-admin-columns'),
            ]),
            $config->get('filter_format', '')
        );
    }

}