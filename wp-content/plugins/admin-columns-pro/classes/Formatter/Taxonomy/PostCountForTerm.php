<?php

namespace ACP\Formatter\Taxonomy;

use AC\Formatter;
use AC\Type\Value;

class PostCountForTerm implements Formatter
{

    private string $post_type;

    private string $taxonomy;

    private string $status;

    public function __construct(string $taxonomy, string $post_type = 'any', ?string $status = null)
    {
        $this->post_type = $post_type;
        $this->taxonomy = $taxonomy;
        $this->status = $status ?: 'any';
    }

    public function format(Value $value): Value
    {
        $posts = get_posts([
            'suppress_filter' => true,
            'fields'          => 'ids',
            'posts_per_page'  => -1,
            'post_type'       => $this->post_type,
            'post_status'     => $this->status,
            'tax_query'       => [
                [
                    'taxonomy' => $this->taxonomy,
                    'field'    => 'id',
                    'terms'    => $value->get_id(),
                ],
            ],
        ]);

        return $value->with_value(count($posts));
    }
}