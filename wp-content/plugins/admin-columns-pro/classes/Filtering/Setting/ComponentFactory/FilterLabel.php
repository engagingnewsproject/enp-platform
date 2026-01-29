<?php

namespace ACP\Filtering\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class FilterLabel extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Top label', 'codepress-admin-columns');
    }

    protected function get_description(Config $config): ?string
    {
        return __("Set the name of the label in the filter menu", 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        $label = strip_tags($config->get('label', ''));

        return new Input\Open(
            'filter_label',
            'text',
            $config->get('filter_label', ''),
            $label
                ? sprintf(__('Any %s', 'codepress-admin-columns'), $label)
                : ''
        );
    }

}