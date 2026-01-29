<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;
use AC\Taxonomy;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if (
            $table_screen instanceof Template ||
            (
                $table_screen instanceof Taxonomy &&
                'fl-builder-template-category' === (string)$table_screen->get_taxonomy()
            )
        ) {
            return new MenuGroup(
                'beaver-builder',
                __('Beaver Builder', 'codepress-admin-columns'),
                14,
                'dashicons-layout'
            );
        }

        return null;
    }

}