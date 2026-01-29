<?php

declare(strict_types=1);

namespace ACA\BP\Settings\ComponentFactory;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use ACA\BP\Value\Formatter\Group\GroupProperty;

class Group extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'group_property_display',
            AC\Setting\Control\OptionCollection::from_array(
                [
                    'title' => __('Title'),
                    'slug'  => __('Slug'),
                ]
            ),
            $config->get('group_property_display', 'title')
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        switch ($config->get('group_property_display', 'title')) {
            case 'slug' :
                $formatters->add(new GroupProperty('slug'));
                break;
            default:
                $formatters->add(new GroupProperty('name'));
        }
    }

}