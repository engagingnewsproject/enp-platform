<?php

declare(strict_types=1);

namespace ACP\Search\Comparison\Post;

use AC\Helper\Select\Options;
use AC\Meta\Query;
use ACP\Helper\Select;
use ACP\Search\Comparison;
use ACP\Search\Operators;

class PrimaryTerm extends Comparison\Meta implements Comparison\RemoteValues
{

    private string $taxonomy;

    private Query $query;

    public function __construct(string $meta_key, string $taxonomy, Query $query)
    {
        $operators = new Operators([
            Operators::EQ,
        ]);

        parent::__construct($operators, $meta_key);

        $this->taxonomy = $taxonomy;
        $this->query = $query;
    }

    public function format_label(string $value): string
    {
        $term = get_term_by('id', $value, $this->taxonomy);

        return $term->name ?? $value;
    }

    public function get_values(): Options
    {
        $values = [];
        foreach ($this->query->get() as $value) {
            $values[$value] = $this->format_label($value);
        }

        return Options::create_from_array($values);
    }

}