<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\ListScreen;
use AC\PostType;
use AC\Registerable;
use AC\TableScreen;

class Table implements Registerable
{

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'fix_manual_product_sort'], 12); // After Sorting is applied
        add_filter(
            'ac/sorting/remember_last_sorting_preference',
            [
                $this,
                'disable_product_sorting_mode_preference',
            ],
            10,
            2
        );
        add_filter('ac/sticky_header/enable', [$this, 'disable_sticky_headers']);
        add_filter('ac/table/query_args_whitelist', [$this, 'add_query_arg_customer_to_whitelist'], 10, 2);
    }

    public function add_query_arg_customer_to_whitelist(array $args, ListScreen $list_screen): array
    {
        if ('shop_order' === $list_screen->get_post_type()) {
            $args[] = '_customer_user';
        }

        return $args;
    }

    public function fix_manual_product_sort(ListScreen $list_screen): void
    {
        if (
            isset($_GET['orderby']) &&
            $list_screen instanceof PostType &&
            $list_screen->get_post_type()->equals('product') &&
            strpos($_GET['orderby'], 'menu_order') !== false &&
            ! filter_input(INPUT_GET, 'orderby')
        ) {
            unset($_GET['orderby']);
        }
    }

    public function disable_sticky_headers(bool $enabled): bool
    {
        if (
            'product' === filter_input(INPUT_GET, 'post_type') &&
            'menu_order title' === filter_input(INPUT_GET, 'orderby')
        ) {
            return false;
        }

        return $enabled;
    }

    public function disable_product_sorting_mode_preference(bool $enabled, TableScreen $table_screen): bool
    {
        if ($table_screen instanceof PostType
            && $table_screen->get_post_type()->equals('product')
            && 'menu_order title' === filter_input(INPUT_GET, 'orderby')) {
            return false;
        }

        return $enabled;
    }

}