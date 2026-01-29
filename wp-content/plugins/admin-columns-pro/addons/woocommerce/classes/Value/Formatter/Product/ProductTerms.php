<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Formatter;
use AC\Type\TaxonomySlug;
use AC\Type\Value;
use AC\Type\ValueCollection;

class ProductTerms implements Formatter
{

    private $taxonomy_slug;

    public function __construct(TaxonomySlug $taxonomy_slug)
    {
        $this->taxonomy_slug = $taxonomy_slug;
    }

    public function format(Value $value)
    {
        $term_ids = new ValueCollection($value->get_id(), []);
        $terms = get_the_terms($value->get_id(), (string)$this->taxonomy_slug);

        if ( ! $terms || is_wp_error($terms)) {
            return $term_ids;
        }

        foreach ($terms as $term) {
            $term_ids->add(new Value($term->term_id, $term));
        }

        return $term_ids;
    }

}