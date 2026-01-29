<?php

declare(strict_types=1);

namespace ACA\Polylang\Service;

use AC;
use AC\Registerable;

class Columns implements Registerable
{

    public const GROUP_NAME = 'polylang';

    public function register(): void
    {
        add_action('ac/column/groups', [$this, 'register_column_groups']);
    }

    public function register_column_groups(AC\Type\Groups $groups): void
    {
        $groups->add(new AC\Type\Group(self::GROUP_NAME, 'Polylang', 25));
    }

}