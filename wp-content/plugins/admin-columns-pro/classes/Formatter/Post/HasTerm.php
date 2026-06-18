<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Helper;
use AC\Type\TaxonomySlug;
use AC\Type\Value;

class HasTerm implements AC\Formatter
{

    private TaxonomySlug $taxonomy;

    private int $term_id;

    public function __construct(TaxonomySlug $taxonomy, int $term_id)
    {
        $this->taxonomy = $taxonomy;
        $this->term_id = $term_id;
    }

    public function format(Value $value)
    {
        $has_term = has_term($this->term_id, (string)$this->taxonomy);

        return $value->with_value(
            Helper\Icon::create()->yes_or_no($has_term)
        );
    }

}