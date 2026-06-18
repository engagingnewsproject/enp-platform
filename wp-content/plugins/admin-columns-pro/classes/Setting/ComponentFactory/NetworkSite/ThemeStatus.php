<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\NetworkSite;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class ThemeStatus extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Theme Status', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'theme_status',
            OptionCollection::from_array([
                'active'    => __('Active Theme', 'codepress-admin-columns'),
                'allowed'   => __('Allowed Themes', 'codepress-admin-columns'),
                'available' => __('Available Themes', 'codepress-admin-columns'),
            ]),
            $config->get('theme_status', 'active')
        );
    }

}