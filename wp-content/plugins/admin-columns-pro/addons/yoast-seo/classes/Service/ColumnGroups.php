<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Service;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Type\Group;

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
            new Group(
                'yoast-seo', 'Yoast SEO', 25,
                $this->location->with_suffix('/assets/images/yoast.svg')->get_url()
            )
        );
    }

}