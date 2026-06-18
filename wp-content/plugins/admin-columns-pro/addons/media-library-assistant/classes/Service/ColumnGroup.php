<?php

declare(strict_types=1);

namespace ACA\MLA\Service;

use AC;
use AC\Registerable;

class ColumnGroup implements Registerable
{

    public const NAME = 'media-library-assistant';

    public function register(): void
    {
        add_action('ac/column/groups', [$this, 'register_column_group']);
    }

    public function register_column_group(AC\Type\Groups $groups): void
    {
        $groups->add(new AC\Type\Group(self::NAME, __('Media Library Assistant'), 25));
    }

}