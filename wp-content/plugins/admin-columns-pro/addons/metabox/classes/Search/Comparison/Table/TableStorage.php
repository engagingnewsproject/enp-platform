<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use ACP;
use ACP\Query\Bindings;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Labels;
use ACP\Search\Operators;
use ACP\Search\Value;

class TableStorage extends ACP\Search\Comparison
{

    protected $table;

    protected $column;

    public function __construct(
        Operators $operators,
        string $table,
        string $column,
        ?string $value_type = null,
        ?Labels $labels = null
    ) {
        parent::__construct($operators, $value_type, $labels);

        $this->table = $table;
        $this->column = $column;
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $binding = new ACP\Query\Bindings\Post();
        $binding->where($wpdb->posts . '.ID in(' . $this->get_subquery($operator, $value) . ')');

        return $binding;
    }

    protected function get_subquery(string $operator, Value $value): string
    {
        $where = ComparisonFactory::create($this->column, $operator, $value);

        return "SELECT ID FROM $this->table WHERE " . $where->prepare();
    }

}