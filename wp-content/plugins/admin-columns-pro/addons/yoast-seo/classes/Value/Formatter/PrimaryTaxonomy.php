<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Helper;
use AC\Type\PostTypeSlug;
use AC\Type\Value;
use WP_Term;

class PrimaryTaxonomy implements AC\Formatter
{

    private string $taxonomy;

    private ?PostTypeSlug $post_type;

    public function __construct(string $taxonomy, PostTypeSlug $post_type = null)
    {
        $this->taxonomy = $taxonomy;
        $this->post_type = $post_type;
    }

    public function format(Value $value): Value
    {
        $meta_value = get_post_meta($value->get_id(), '_yoast_wpseo_primary_' . $this->taxonomy, true);

        if ( ! $meta_value) {
            throw new ValueNotFoundException();
        }

        $term = get_term($meta_value, $this->taxonomy);

        if ( ! $term instanceof WP_Term) {
            throw new ValueNotFoundException();
        }

        $terms = Helper\Taxonomy::create()->get_term_links(
            [
                $term,
            ],
            (string)$this->post_type
        );

        if (empty($terms)) {
            throw new ValueNotFoundException();
        }

        return $value->with_value(
            Helper\Strings::create()->enumeration_list($terms, 'and')
        );
    }

}