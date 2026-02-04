<?php

declare(strict_types=1);

namespace ACP\Table;

use LogicException;

class TaxonomyRepository
{

    public function exists(string $taxonomy): bool
    {
        static $taxonomies;

        if (null === $taxonomies) {
            $taxonomies = $this->find_all();
        }

        return in_array($taxonomy, $taxonomies, true);
    }

    public function find_all(): array
    {
        if ( ! did_action('init')) {
            throw new LogicException("Call after the `init` hook.");
        }

        $taxonomies = get_taxonomies(['show_ui' => true]);

        unset($taxonomies['post_format']);

        if ( ! get_option('link_manager_enabled')) {
            unset($taxonomies['link_category']);
        }

        return (array)apply_filters('acp/taxonomies', $taxonomies);
    }

}