<?php

declare(strict_types=1);

namespace ACA\EC\Sorting;

use AC;
use AC\Type\ColumnId;
use ACP\Sorting\ModelFactory;
use WP_Query;

class EventSortingFix
{

    private AC\ListScreen $list_screen;

    private ModelFactory $model_factory;

    public function __construct(AC\ListScreen $list_screen, ModelFactory $model_factory)
    {
        $this->list_screen = $list_screen;
        $this->model_factory = $model_factory;
    }

    public function register(): void
    {
        add_action('pre_get_posts', [$this, 'deregister_tribe_sorting_hooks']);
    }

    public function deregister_tribe_sorting_hooks(WP_Query $wp_query): void
    {
        if ( ! class_exists('Tribe__Events__Main') || ! class_exists(
                'Tribe__Events__Admin_List'
            ) || ! $wp_query->is_main_query()) {
            return;
        }

        $orderby = $wp_query->get('orderby');

        if ( ! $orderby) {
            return;
        }

        $column = $this->list_screen->get_column(new ColumnId((string)$orderby));

        if ( ! $column) {
            return;
        }

        $has_model = $this->model_factory->create($column, $this->list_screen->get_table_screen());

        if ( ! $has_model) {
            return;
        }

        add_filter('posts_clauses_request', function ($pieces) {
            global $wpdb;

            // Code in Events Calendar has not been changed to support custom sorting.
            $pieces['where'] = str_replace(
                ' post_parent = 0',
                " $wpdb->posts.post_parent = 0",
                $pieces['where']
            );

            return $pieces;
        }, 101, 2);

        remove_filter('posts_fields', ['Tribe__Events__Admin_List', 'events_search_fields']);
        remove_filter('posts_clauses', ['Tribe__Events__Admin_List', 'sort_by_event_date'], 11);
    }

}