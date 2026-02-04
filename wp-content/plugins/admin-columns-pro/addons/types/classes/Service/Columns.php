<?php

declare(strict_types=1);

namespace ACA\Types\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Type\Groups;

final class Columns implements AC\Registerable
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
            new AC\Type\Group(
                'types',
                'Toolset Types',
                14,
                $this->location->with_suffix('/assets/images/toolset.svg')->get_url()
            )
        );
        $groups->add(
            new AC\Type\Group(
                'types_relationship',
                'Toolset Types Relations',
                14,
                $this->location->with_suffix('/assets/images/toolset.svg')->get_url()
            )
        );
    }

}