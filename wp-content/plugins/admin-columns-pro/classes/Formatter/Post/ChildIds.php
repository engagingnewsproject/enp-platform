<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;

class ChildIds implements AC\Formatter
{

    private array $post_types;

    public function __construct(array $post_types)
    {
        $this->post_types = $post_types;
    }

    public function format(Value $value)
    {
        $post = get_post($value->get_id());
        if ( ! $post) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $children = $this->get_child_ids($value->get_id());

        if ( ! $children) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $children);
    }

    private function get_child_ids(int $post_id): array
    {
        return get_posts([
            'post_type'      => $this->post_types,
            'post_parent'    => $post_id,
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ]);
    }

}