<?php

declare(strict_types=1);

namespace ACA\BP\Service;

use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Type\Group;
use AC\Type\Groups;

class ColumnGroups implements Registerable
{

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
                'buddypress',
                __('BuddyPress', 'codepress-admin-columns'),
                15,
                $this->location->with_suffix('/assets/images/buddypress.svg')->get_url()
            )
        );
        $groups->add(
            new Group(
                'buddypress_profile',
                __('BuddyPress Profile Fields', 'codepress-admin-columns'),
                15,
                $this->location->with_suffix('/assets/images/buddypress.svg')->get_url()
            )
        );
    }

}