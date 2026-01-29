<?php

declare(strict_types=1);

namespace ACA\RankMath\Service;

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
        $icon = $this->location->with_suffix('/assets/images/rank-math.svg')->get_url();

        $definition = [
            'rank-math'                 => 'Rank Math',
            'rank-math-robots-meta'     => 'Rank Math - Robots Meta',
            'rank-math-social-facebook' => 'Rank Math - Facebook',
        ];

        foreach ($definition as $group => $label) {
            $groups->add(
                new AC\Type\Group(
                    $group,
                    $label,
                    25,
                    $icon
                )
            );
        }
    }

}