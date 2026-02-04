<?php

declare(strict_types=1);

namespace ACA\MetaBox\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;

final class ColumnGroups implements Registerable
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

    public function register_column_groups(AC\Type\Groups $groups): void
    {
        $groups->add(
            new AC\Type\Group(
                'metabox',
                'MetaBox',
                14,
                $this->location->with_suffix('/assets/images/metabox.svg')->get_url()
            )
        );
        $groups->add(
            new AC\Type\Group(
                'metabox_relation',
                'MetaBox Relation',
                14,
                $this->location->with_suffix('/assets/images/metabox.svg')->get_url()
            )
        );
        $groups->add(
            new AC\Type\Group(
                'metabox_custom',
                'Custom MetaBox',
                14,
                $this->location->with_suffix('/assets/images/metabox.svg')->get_url()
            )
        );
    }

}