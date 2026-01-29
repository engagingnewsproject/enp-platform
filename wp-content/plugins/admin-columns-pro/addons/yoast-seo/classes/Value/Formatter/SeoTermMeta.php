<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Type\Value;

class SeoTermMeta implements AC\Formatter
{

    private string $meta_key;

    private AC\Type\TaxonomySlug $taxonomy;

    public function __construct(AC\Type\TaxonomySlug $taxonomy, string $meta_key)
    {
        $this->meta_key = $meta_key;
        $this->taxonomy = $taxonomy;
    }

    public function format(Value $value): Value
    {
        $meta = get_option('wpseo_taxonomy_meta');

        if ( ! is_array($meta)) {
            return $value->with_value(false);
        }

        return isset($meta[(string)$this->taxonomy][$value->get_id()][$this->meta_key])
            ? $value->with_value($meta[(string)$this->taxonomy][$value->get_id()][$this->meta_key])
            : $value->with_value(false);
    }

}