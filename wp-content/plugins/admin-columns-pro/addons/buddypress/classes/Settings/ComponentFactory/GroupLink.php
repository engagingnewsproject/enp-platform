<?php

declare(strict_types=1);

namespace ACA\BP\Settings\ComponentFactory;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use ACA\BP\Value\Formatter;

class GroupLink extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Link To', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'group_link_to',
            $this->get_display_options(),
            $config->get('group_link_to', '')
        );
    }

    private function get_display_options(): AC\Setting\Control\OptionCollection
    {
        $options = [
            'edit_group' => __('Edit Group'),
            'view_group' => __('View Group'),
        ];

        asort($options);

        $options = array_merge(['' => __('None')], $options);

        return AC\Setting\Control\OptionCollection::from_array($options);
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        $formatters->add(new Formatter\Group\GroupLink($config->get('group_link_to', '')));
    }

}