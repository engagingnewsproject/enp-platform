<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison;

use AC\Helper\Select\Options\Paginated;
use ACP;
use ACP\Helper\Select\Taxonomy\LabelFormatter;
use ACP\Helper\Select\Taxonomy\LabelFormatter\TermName;
use ACP\Helper\Select\Taxonomy\PaginatedFactory;
use ACP\Search\Operators;
use WP_Term;

class TaxonomyAdvanced extends ACP\Search\Comparison\Meta
    implements ACP\Search\Comparison\SearchableValues
{

    protected $taxonomy;

    public function __construct(array $taxonomy, string $meta_key)
    {
        parent::__construct(
            new Operators([
                Operators::EQ,
                Operators::NEQ,
                Operators::IS_EMPTY,
                Operators::NOT_IS_EMPTY,
            ]),
            $meta_key
        );
        $this->taxonomy = $taxonomy;
    }

    private function get_label_formatter(): LabelFormatter
    {
        return new TermName();
    }

    public function format_label($value): string
    {
        $term = get_term($value);

        return $term instanceof WP_Term
            ? $this->get_label_formatter()->format_label($term)
            : $value;
    }

    public function get_values(string $search, int $page): Paginated
    {
        return (new PaginatedFactory())->create([
            'search'   => $search,
            'page'     => $page,
            'taxonomy' => $this->taxonomy,
        ], $this->get_label_formatter());
    }

}