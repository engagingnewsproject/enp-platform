<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class GroupCloneField implements AC\Formatter
{

    private AC\MetaType $meta_type;

    private string $field_id;

    private string $sub_field;

    public function __construct(AC\MetaType $meta_type, string $field_id, string $sub_field)
    {
        $this->meta_type = $meta_type;
        $this->field_id = $field_id;
        $this->sub_field = $sub_field;
    }

    public function format(Value $value): AC\Type\ValueCollection
    {
        $metabox_value = rwmb_get_value($this->field_id, ['object_type' => (string)$this->meta_type], $value->get_id());

        if ( ! is_array($metabox_value)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $values = new AC\Type\ValueCollection($value->get_id(), []);

        foreach ($metabox_value as $record) {
            if (isset($record[$this->sub_field])) {
                $values->add(new Value($value->get_id(), $record[$this->sub_field]));
            }
        }

        return $values;
    }

}