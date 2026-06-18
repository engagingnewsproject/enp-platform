<?php

namespace ACP\Sorting\Controller;

use AC;
use ACP\Sorting\Type\SortType;
use ACP\Sorting\UserPreference;

/**
 * When you revisit a page, set the orderby variable so WordPress prints the columns headers properly
 */
class RequestSetterHandler
{

    public function handle(AC\ListScreen $list_screen): void
    {
        $request = new AC\Request();

        // Ignore media grid
        if ('grid' === $request->get('mode')) {
            return;
        }

        $sort_type = SortType::create_by_request_globals();

        if ($sort_type) {
            return;
        }

        $sort_type = UserPreference\SortType::create($list_screen)->get();

        if ( ! $sort_type) {
            $sort_type = SortType::create_by_list_screen($list_screen);
        }

        if ( ! $sort_type) {
            return;
        }

        // Set $_GET and $_REQUEST (used by WP_User_Query)
        $_REQUEST['orderby'] = $_GET['orderby'] = $sort_type->get_order_by();
        $_REQUEST['order'] = $_GET['order'] = $sort_type->is_descending() ? 'desc' : 'asc';
    }

}