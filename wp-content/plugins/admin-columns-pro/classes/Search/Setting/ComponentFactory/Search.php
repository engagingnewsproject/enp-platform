<?php

namespace ACP\Search\Setting\ComponentFactory;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;

class Search extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Enable Smart Filtering', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_toggle('search', null, $config->has('search') ? $config->get('search') : 'on');
    }

    protected function get_attributes(Config $config, AttributeCollection $attributes): AttributeCollection
    {
        return new AttributeCollection([
            AttributeFactory::create_help_reference('doc-smart-filtering'),
        ]);
    }

}