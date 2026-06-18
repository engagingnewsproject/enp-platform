<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\PostType;

use AC;
use AC\Type\Value;
use WP_Taxonomy;

class TaxonomyLabel implements AC\Formatter
{

    public function format(Value $value)
    {
        $taxonomies = $value->get_value();

        if ( ! is_array($taxonomies)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];
        foreach ($taxonomies as $taxonomy) {
            $taxonomy = get_taxonomy($taxonomy);

            if ($taxonomy instanceof WP_Taxonomy) {
                $values[] = sprintf(
                    '<a href="%s">%s</a>',
                    $this->get_taxonomy_link($taxonomy->name),
                    $taxonomy->label
                );
            }
        }

        return $value->with_value(implode(', ', $values));
    }

    private function get_taxonomy_link(string $taxonomy): string
    {
        return add_query_arg(['taxonomy' => $taxonomy], admin_url('edit-tags.php'));
    }

}