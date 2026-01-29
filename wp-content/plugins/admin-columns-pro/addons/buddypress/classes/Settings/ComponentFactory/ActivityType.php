<?php

declare(strict_types=1);

namespace ACA\BP\Settings\ComponentFactory;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class ActivityType extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Activity Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'activity_type',
            $this->get_display_options(),
            $config->get('activity_type', '')
        );
    }

    protected function get_display_options(): AC\Setting\Control\OptionCollection
    {
        $options = [
            '' => __('All'),
        ];

        foreach (bp_activity_get_actions()->activity as $activity) {
            $options[$activity['key']] = $activity['value'];
        }

        return AC\Setting\Control\OptionCollection::from_array($options);
    }

}