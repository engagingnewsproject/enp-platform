<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class FlexDisplay extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'flex_display',
            OptionCollection::from_array([
                'count'     => __('Layout Type Count', 'codepress-admin-columns'),
                'structure' => __('Layout Structure', 'codepress-admin-columns'),
            ]),
            $config->get('flex_display', 'count')
        );
    }

}