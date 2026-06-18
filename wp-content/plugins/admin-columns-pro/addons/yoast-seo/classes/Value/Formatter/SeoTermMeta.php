<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\TaxonomySlug;
use AC\Type\Value;

class SeoTermMeta implements AC\Formatter
{

    private TaxonomySlug $taxonomy;

    private string $meta_key;

    public function __construct(TaxonomySlug $taxonomy, string $meta_key)
    {
        $this->taxonomy = $taxonomy;
        $this->meta_key = $meta_key;
    }

    public function format(Value $value): Value
    {
        $meta = get_option('wpseo_taxonomy_meta');

        if ( ! is_array($meta)) {
            throw new ValueNotFoundException();
        }

        $term_meta = $meta[(string)$this->taxonomy][$value->get_id()][$this->meta_key] ?? null;

        if ( ! $term_meta) {
            throw new ValueNotFoundException();
        }

        return $value->with_value($term_meta);
    }

}