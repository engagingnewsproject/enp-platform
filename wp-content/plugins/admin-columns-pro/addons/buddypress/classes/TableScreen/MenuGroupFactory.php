<?php

declare(strict_types=1);

namespace ACA\BP\TableScreen;

use AC\Admin\Type\MenuGroup;
use AC\TableScreen;
use AC\Type\TableId;

class MenuGroupFactory implements \AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if (
            $table_screen->get_id()->equals(new TableId('bp-groups')) ||
            $table_screen->get_id()->equals(new TableId('bp-activity')) ||
            $table_screen->get_id()->equals(new TableId('bp-email'))
        ) {
            return new MenuGroup(
                'buddypress',
                __('BuddyPress'),
                22,
                'dashicons-buddicons-buddypress-logo'
            );
        }
        if (
            $table_screen->get_id()->equals(new TableId('wp-taxonomy_bp_member_type')) ||
            $table_screen->get_id()->equals(new TableId('wp-taxonomy_bp_group_type'))
        ) {
            return new MenuGroup(
                'buddypress',
                __('BuddyPress'),
                24,
                'dashicons-buddicons-buddypress-logo'
            );
        }

        return null;
    }

}