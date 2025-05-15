<?php

namespace ACA\GravityForms\Query;

use ACP;

final class Entry extends ACP\Query
{

    public function register(): void
    {
        add_filter('gform_gf_query_sql', [$this, 'parse_search_query']);
    }

    public function parse_search_query(array $query): array
    {
        foreach ($this->bindings as $binding) {
            if ($binding->get_where()) {
                $query['where'] .= "\nAND " . $binding->get_where();
            }

            if ($binding->get_join()) {
                $query['join'] .= "\n" . $binding->get_join();
            }
        }

        return $query;
    }

}