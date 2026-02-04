<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\Service;

use AC\Registerable;
use WP_Term;

class ColumnRenderTaxonomyFilter implements Registerable
{

    public function register(): void
    {
        add_filter('ac/formatter/term/link', [$this, 'render'], 10, 4);
    }

    public function render(string $link, WP_Term $term, string $post_type): string
    {
        if ('fl-builder-template' !== $post_type) {
            return $link;
        }

        $type = filter_input(INPUT_GET, 'fl-builder-template-type') ?? 'layout';

        $link = ac_helper()->taxonomy->get_filter_by_term_url(
            $term,
            $post_type
        );

        return add_query_arg(
            'fl-builder-template-type',
            $type,
            $link
        );
    }

}