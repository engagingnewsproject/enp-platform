<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting\FormatValue;

use ACP\Sorting\FormatValue;

class Taxonomy implements FormatValue
{

    public function format_value($value)
    {
        $term_ids = maybe_unserialize($value);

        if (empty($term_ids)) {
            return null;
        }

        $term_id = is_array($term_ids) ? $term_ids[0] : $term_ids;

        $term = get_term($term_id);

        return $term
            ? $term->name
            : null;
    }

}