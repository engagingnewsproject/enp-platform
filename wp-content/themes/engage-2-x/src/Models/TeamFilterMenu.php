<?php

namespace Engage\Models;

class TeamFilterMenu extends FilterMenu
{
    public function setFilters()
    {
        $filters = [
            'title' => $this->title,
            'slug'  => $this->slug,
            'structure' => $this->structure,
            'link'  => false,
            'terms' => []
        ];

        // Get all blogs-category terms
        $taxonomy = 'team_category';
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ]);

        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                // Skip uncategorized or any other unwanted terms
                if ($term->slug === 'uncategorized') {
                    continue;
                }
                $filters['terms'][$term->slug] = $this->buildFilterTerm($term, false, 'team');
            }
        }

        return $filters;
    }
}