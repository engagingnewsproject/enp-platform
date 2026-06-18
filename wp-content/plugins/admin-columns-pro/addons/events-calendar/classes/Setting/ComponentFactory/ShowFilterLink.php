<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollectionFactory\ToggleOptionCollection;

class ShowFilterLink extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Link to Filtered Overview', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_toggle(
            'show_filter_link',
            (new ToggleOptionCollection())->create(),
            $config->get('show_filter_link', 'on')
        );
    }

}
