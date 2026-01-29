<?php

namespace ACP\Setting\ComponentFactory;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class LinkCountType extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'link_count_type',
            AC\Setting\Control\OptionCollection::from_array([
                ''         => __('Total Links', 'codepress-admin-columns'),
                'internal' => __('Internal Links', 'codepress-admin-columns'),
                'external' => __('External Links', 'codepress-admin-columns'),
            ]),
            $config->get('link_count_type', '')
        );
    }

}