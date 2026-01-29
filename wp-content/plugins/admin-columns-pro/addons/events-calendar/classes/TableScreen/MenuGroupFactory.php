<?php

declare(strict_types=1);

namespace ACA\EC\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;
use AC\TableScreen\Post;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if ($table_screen instanceof Post && in_array(
                (string)$table_screen->get_post_type(),
                $this->get_post_types()
            )) {
            return new MenuGroup(
                'events-calendar',
                __('Events Calendar', 'codepress-admin-columns'),
                14,
                'dashicons-calendar-alt'
            );
        }

        return null;
    }

    private function get_post_types(): array
    {
        return [
            'tribe_organizer',
            'tribe_events',
            'tribe_event_series',
            'tribe_venue',
        ];
    }

}