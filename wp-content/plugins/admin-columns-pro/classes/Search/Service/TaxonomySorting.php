<?php

declare(strict_types=1);

namespace ACP\Search\Service;

use AC;
use AC\Registerable;
use ACP;

/**
 * Service that sets the orderby parameter when filtering taxonomies with Admin Columns Pro.
 * Hierarchical taxonomies will filter out child terms that match a filter when no sorting
 * is defined in the table. This service ensures proper sorting is applied during search/filter
 * operations to prevent child terms from being excluded from the results.
 */
class TaxonomySorting implements Registerable
{

    use ACP\Search\DefaultSegmentTrait;

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'set_sorting_while_searching'], 20);
    }

    /**
     * Sets default sorting for taxonomy list screens when filtering.
     * Ensures that hierarchical taxonomy terms are properly displayed when using
     * Admin Columns Pro filtering by setting a default orderby parameter if none exists.
     */
    public function set_sorting_while_searching(AC\ListScreen $list_screen): void
    {
        $table_screen = $list_screen->get_table_screen();

        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy) {
            return;
        }

        if( ! is_taxonomy_hierarchical( (string)$table_screen->get_taxonomy())){
            return;
        }

        if (isset($_REQUEST['orderby'])) {
            return;
        }

        if ( ! $this->has_filtering_applied($list_screen)) {
            return;
        }

        $_REQUEST['orderby'] = 'name';
    }

    private function has_filtering_applied(AC\ListScreen $list_screen): bool
    {
        $search_keys = ['ac-rules', 'ac-filter'];

        if ( ! empty(array_intersect($search_keys, array_keys($_REQUEST)))) {
            return true;
        }

        $segment = $this->get_default_segment($list_screen);

        if ( ! $segment) {
            return false;
        }

        return ! empty(array_intersect($search_keys, array_keys($segment->get_url_parameters())));
    }

}