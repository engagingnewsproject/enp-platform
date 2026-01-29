<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WP_Term;

class TermIds implements AC\Formatter
{

    public function format(Value $value)
    {
        $terms = $value->get_value();

        if (empty($terms)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if (is_numeric($terms)) {
            $terms = [get_term($terms)];
        }

        if ($terms instanceof WP_Term) {
            $terms = [$terms];
        }

        $collection = new ValueCollection($value->get_id());

        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $collection->add(
                    new Value($term->term_id, $term->name)
                );
            }
        }

        return $collection;
    }

}