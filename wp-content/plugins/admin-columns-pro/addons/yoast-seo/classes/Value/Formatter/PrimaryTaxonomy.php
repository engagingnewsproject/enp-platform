<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Type\Value;
use WP_Term;

class PrimaryTaxonomy implements AC\Formatter
{

    private string $taxonomy;

    private ?AC\Type\PostTypeSlug $post_type;

    public function __construct(string $taxonomy, AC\Type\PostTypeSlug $post_type = null)
    {
        $this->taxonomy = $taxonomy;
        $this->post_type = $post_type;
    }

    public function format(Value $value): Value
    {
        $meta_value = get_post_meta($value->get_id(), '_yoast_wpseo_primary_' . $this->taxonomy, true);

        if ( ! $meta_value) {
            return $value->with_value(false);
        }

        $term = get_term($meta_value, $this->taxonomy);

        if ( ! $term instanceof WP_Term) {
            return $value->with_value(false);
        }

        $terms = ac_helper()->taxonomy->get_term_links(
            [
                $term,
            ],
            $this->post_type ?: null
        );

        return ! empty($terms)
            ? $value->with_value(ac_helper()->string->enumeration_list($terms, 'and'))
            : $value->with_value(false);
    }

}