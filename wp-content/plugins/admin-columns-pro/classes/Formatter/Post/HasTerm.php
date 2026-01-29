<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class HasTerm implements AC\Formatter
{

    private $term_id;

    private $taxonomy;

    public function __construct(AC\Type\TaxonomySlug $taxonomy, int $term_id)
    {
        $this->term_id = $term_id;
        $this->taxonomy = $taxonomy;
    }

    public function format(Value $value)
    {
        $has_term = has_term($this->term_id, (string)$this->taxonomy);

        return $value->with_value(
            ac_helper()->icon->yes_or_no($has_term)
        );
    }

}