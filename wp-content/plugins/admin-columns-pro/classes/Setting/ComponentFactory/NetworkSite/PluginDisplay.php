<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\NetworkSite;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class PluginDisplay extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'plugin_display',
            OptionCollection::from_array([
                'count' => __('Count', 'codepress-admin-columns'),
                'list'  => __('List', 'codepress-admin-columns'),
            ]),
            $config->get('plugin_display', 'count')
        );
    }

}