<?php

namespace ACP\Sorting\Setting\ComponentFactory;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;

class Sort extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Sorting', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_toggle(
            'sort',
            null,
            $config->has('sort')
                ? $config->get('sort')
                : 'on' // default is on
        );
    }

    protected function get_attributes(Config $config, AttributeCollection $attributes): AttributeCollection
    {
        return new AttributeCollection([
            AttributeFactory::create_help_reference('doc-sorting'),
        ]);
    }

}