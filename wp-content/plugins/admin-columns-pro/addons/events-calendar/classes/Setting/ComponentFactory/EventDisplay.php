<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class EventDisplay extends BaseComponentFactory
{

    private const KEY = 'event_display';

    protected function get_label(Config $config): ?string
    {
        return __('Show Events', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::KEY,
            OptionCollection::from_array([
                'all'    => __('All'),
                'future' => __('Upcoming Events', 'codepress-admin-columns'),
                'past'   => __('Past Events', 'codepress-admin-columns'),
            ]),
            $config->get(self::KEY, 'all')
        );
    }

}