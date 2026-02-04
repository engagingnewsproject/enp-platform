<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if ($table_screen instanceof Entry) {
            return new MenuGroup(
                'gravity_forms',
                sprintf('%s - %s', __('Gravity Forms'), __('Entries', 'codepress-admin-columns')),
                20
            );
        }

        return null;
    }

}