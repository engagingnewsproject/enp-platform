<?php

declare(strict_types=1);

namespace ACA\EC\Service;

use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Type\Group;
use AC\Type\Groups;

final class ColumnGroups implements Registerable
{

    public const EVENTS_CALENDAR = 'events_calendar';
    public const EVENTS_CALENDAR_FIELDS = 'events_calendar_fields';

    private Absolute $location;

    public function __construct(Absolute $location)
    {
        $this->location = $location;
    }

    public function register(): void
    {
        add_action('ac/column/groups', [$this, 'register_column_groups']);
    }

    public function register_column_groups(Groups $groups): void
    {
        $groups->add(
            new Group(
                self::EVENTS_CALENDAR, 'The Events Calendar', 14,
                $this->location->with_suffix('/assets/images/events.svg')->get_url()
            )
        );
        $groups->add(
            new Group(
                self::EVENTS_CALENDAR_FIELDS,
                'The Events Calendar' . ' - ' . __('Additional Fields', 'tribe-events-calendar-pro'),
                14,
                $this->location->with_suffix('/assets/images/events.svg')->get_url()
            )
        );
    }

}