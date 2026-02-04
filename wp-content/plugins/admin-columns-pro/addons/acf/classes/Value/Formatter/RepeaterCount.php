<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\TableScreenContext;
use AC\Type\Value;
use ACA\ACF\Utils\AcfId;

class RepeaterCount implements Formatter
{

    private string $field_id;

    private TableScreenContext $table_context;

    public function __construct(TableScreenContext $table_context, string $field_id)
    {
        $this->field_id = $field_id;
        $this->table_context = $table_context;
    }

    public function format(Value $value): Value
    {
        $raw = get_field($this->field_id, AcfId::get_id($value->get_id(), $this->table_context), false);

        if (empty($raw)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(count($raw));
    }

}