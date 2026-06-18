<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class EventDates extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Event Date', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'event_date',
            AC\Setting\Control\OptionCollection::from_array([
                '_EventStartDate' => __('Start Date'),
                '_EventEndDate'   => __('End Date'),
            ]),
            $config->get('event_date', '_EventStartDate')
        );
    }

}