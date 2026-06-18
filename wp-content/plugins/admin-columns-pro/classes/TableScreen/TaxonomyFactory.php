<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use WP_Screen;
use WP_Taxonomy;

class TaxonomyFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return $this->create_table_screen($this->get_taxonomy($id));
    }

    public function can_create(TableId $id): bool
    {
        return null !== $this->get_taxonomy($id);
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return $this->create_table_screen(get_taxonomy($screen->taxonomy));
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'edit-tags' === $screen->base
               && $screen->taxonomy
               && $screen->taxonomy === filter_input(INPUT_GET, 'taxonomy');
    }

    private function get_taxonomy(TableId $id): ?WP_Taxonomy
    {
        if ( ! str_starts_with((string)$id, 'wp-taxonomy_')) {
            return null;
        }

        $taxonomy = get_taxonomy(substr((string)$id, 12));

        return $taxonomy ?: null;
    }

    public function create_table_screen(WP_Taxonomy $taxonomy): Taxonomy
    {
        $post_types = $taxonomy->object_type ?: [];
        $post_type = reset($post_types);

        if ( ! $post_type || ! post_type_exists($post_type)) {
            $post_type = null;
        }

        return new Taxonomy(
            $taxonomy,
            new AC\Type\Url\ListTable\Taxonomy(
                $taxonomy->name,
                $post_type
            )
        );
    }

}