<?php

declare(strict_types=1);

namespace ACA\GravityForms\Service;

use AC;
use AC\ListScreen;
use ACA\GravityForms\TableElement\EntryFilters;
use ACA\GravityForms\TableElement\WordPressNotifications;
use ACA\GravityForms\TableScreen;

class Entry implements AC\Registerable
{

    private $notifications;

    public function __construct(WordPressNotifications $notifications)
    {
        $this->notifications = $notifications;
    }

    public function register(): void
    {
        add_filter('gform_entry_list_columns', [$this, 'remove_selector_column'], 11, 2);
        add_action('ac/table/screen', [$this, 'register_table_rows']);
        add_action('ac/admin_head', [$this, 'hide_entry_filters']);
        add_action('ac/admin_head', [$this, 'hide_wordpress_notifications']);
    }

    public function hide_entry_filters(ListScreen $list_screen)
    {
        $table_screen = $list_screen->get_table_screen();

        if ( ! $table_screen instanceof TableScreen\Entry || (new EntryFilters())->is_enabled($list_screen)) {
            return;
        }

        ?>
		<style>
			#entry_search_container {
				display: none !important;
			}
		</style>
        <?php
    }

    public function hide_wordpress_notifications(ListScreen $list_screen): void
    {
        $table_screen = $list_screen->get_table_screen();

        if ( ! $table_screen instanceof TableScreen\Entry || $this->notifications->is_enabled($list_screen)) {
            return;
        }
        ?>
		<style>
			#gf-wordpress-notices {
				display: none !important;
			}
		</style>
        <?php
    }

    public function register_table_rows(AC\TableScreen $table_screen)
    {
        if ( ! $table_screen instanceof TableScreen\Entry) {
            return;
        }

        $table_rows = new TableScreen\TableRows\Entry($table_screen);
        $table_rows->register();
    }

    public function remove_selector_column($columns)
    {
        unset($columns['column_selector']);

        return $columns;
    }

}