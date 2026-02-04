<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class GroupedByCollection implements Formatter
{

    public function format(Value $value)
    {
        $parents = $this->get_parent_posts($value->get_id());

        if (empty($parents)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $parents);
    }

    private function get_parent_posts($id): array
    {
        return get_posts([
            'fields'         => 'ids',
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_children',
                    'value'   => serialize((int)$id),
                    'compare' => 'LIKE',
                ],
            ],
            'tax_query'      => [
                [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'grouped',
                ],
            ],
        ]);
    }

}