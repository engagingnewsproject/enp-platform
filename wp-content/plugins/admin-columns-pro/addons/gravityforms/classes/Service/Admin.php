<?php

declare(strict_types=1);

namespace ACA\GravityForms\Service;

use AC;
use ACA\GravityForms\TableElement\EntryFilters;
use ACA\GravityForms\TableElement\WordPressNotifications;
use ACA\GravityForms\TableScreen\Entry;
use ACP;
use ACP\Settings\ListScreen\TableElements;

final class Admin implements AC\Registerable
{

    public function register(): void
    {
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements'], 10, 2);
    }

    public function add_table_elements(TableElements $collection, AC\TableScreen $table_screen)
    {
        if ($table_screen instanceof Entry) {
            $collection->remove(new ACP\Settings\ListScreen\TableElement\Search())
                       ->add(new EntryFilters())
                       ->add(new WordPressNotifications());
        }
    }

}