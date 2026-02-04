<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\TableScreenContext;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\ACF\Utils\AcfId;

class RawRepeater implements Formatter
{

    private string $field_id;

    private TableScreenContext $table_context;

    private string $sub_field;

    public function __construct(TableScreenContext $table_context, string $field_id, string $sub_field)
    {
        $this->field_id = $field_id;
        $this->table_context = $table_context;
        $this->sub_field = $sub_field;
    }

    public function format(Value $value): ValueCollection
    {
        $raw = get_field($this->field_id, AcfId::get_id($value->get_id(), $this->table_context), false);

        if (empty($raw)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new ValueCollection($value->get_id());

        foreach ($raw as $repeater_value) {
            if (isset($repeater_value[$this->sub_field])) {
                $collection->add(new Value($value->get_id(), $repeater_value[$this->sub_field]));
            }
        }

        return $collection;
    }

}