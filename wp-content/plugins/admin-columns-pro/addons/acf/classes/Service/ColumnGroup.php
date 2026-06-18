<?php

declare(strict_types=1);

namespace ACA\ACF\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Type\Group;

class ColumnGroup implements AC\Registerable
{

    public const SLUG = 'acf';

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
            new Group(
                self::SLUG,
                'Advanced Custom Fields',
                14,
                $this->location->with_suffix('/assets/images/acf.png')->get_url()
            )
        );

        foreach (acf_get_field_groups() as $group) {
            $groups->add(
                new Group(
                    self::SLUG . $group['ID'],
                    sprintf('%s / %s', 'ACF', $group['title']),
                    15,
                    $this->location->with_suffix('/assets/images/acf.png')->get_url()
                )
            );
        }
    }

}