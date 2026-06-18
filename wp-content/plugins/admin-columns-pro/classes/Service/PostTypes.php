<?php

declare(strict_types=1);

namespace ACP\Service;

use AC;
use AC\Registerable;

final class PostTypes implements Registerable
{

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'deregister_layout_query_var']);
    }

    /**
     * This quickfix deregisters the 'layout' query variable on the table screen when the Post Type 'Layout' exist
     * Otherwise, the layout param in the URL used in Admin Columns is parsed as name identifier to search withing posts
     */
    public function deregister_layout_query_var(AC\ListScreen $list_screen): void
    {
        if ($list_screen->get_table_screen() instanceof AC\PostType && post_type_exists('layout')) {
            add_filter('query_vars', static function ($vars) {
                $key = array_search('layout', $vars, true);

                if ($key !== false) {
                    unset($vars[$key]);
                }

                return $vars;
            });
        }
    }

}