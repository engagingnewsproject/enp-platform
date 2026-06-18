<?php

declare(strict_types=1);

namespace ACP\Export\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;

class ListTable implements AC\Formatter
{

    private AC\ListTable $list_table;

    private AC\Type\ColumnId $column_id;

    public function __construct(AC\ListTable $list_table, AC\Type\ColumnId $column_id)
    {
        $this->list_table = $list_table;
        $this->column_id = $column_id;
    }

    public function format(Value $value): Value
    {
        $data = $this->list_table->render_cell(
            (string)$this->column_id,
            $value->get_id()
        );

        if (null === $data) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            strip_tags((string)$data)
        );
    }

}