<?php

declare(strict_types=1);

namespace ACP\Admin;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;
use ACP\TableScreen\NetworkSite;
use ACP\TableScreen\NetworkUser;
use ACP\TableScreen\Taxonomy;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        switch (true) {
            case $table_screen instanceof NetworkUser:
            case $table_screen instanceof NetworkSite:
                return new MenuGroup('network', __('Network'), 10, 'dashicons-networking');
            case $table_screen instanceof Taxonomy:
                $taxonomy = get_taxonomy((string)$table_screen->get_taxonomy());

                if ( ! $taxonomy->show_in_nav_menus) {
                    return new MenuGroup(
                        'taxonomy-hidden',
                        sprintf('%s (%s)', __('Taxonomy'), __('hidden')),
                        41,
                        'material-label'
                    );
                }

                return new MenuGroup('taxonomy', __('Taxonomy'), 30, 'material-label');
            default:
                return null;
        }
    }

}