<?php

declare(strict_types=1);

namespace ACA\SeoPress\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;

final class ColumnGroup implements Registerable
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
        $icon = $this->location->with_suffix('/assets/images/seopress.svg')->get_url();

        $groups->add(
            new AC\Type\Group(
                'seopress',
                'SeoPress',
                28,
                $icon
            )
        );
        $groups->add(
            new AC\Type\Group(
                'seopress_social',
                'SeoPress Social',
                28,
                $icon
            )
        );
    }

}