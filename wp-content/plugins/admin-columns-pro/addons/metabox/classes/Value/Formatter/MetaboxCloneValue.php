<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class MetaboxCloneValue implements AC\Formatter
{

    private AC\MetaType $meta_type;

    private string $field_id;

    public function __construct(AC\MetaType $meta_type, string $field_id)
    {
        $this->meta_type = $meta_type;
        $this->field_id = $field_id;
    }

    public function format(Value $value)
    {
        $metabox_values = rwmb_get_value(
            $this->field_id,
            ['object_type' => (string)$this->meta_type],
            $value->get_id()
        );

        if (empty($metabox_values)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new AC\Type\ValueCollection($value->get_id());

        foreach ($metabox_values as $metabox_value) {
            $collection->add(
                new Value($value->get_id(), $metabox_value)
            );
        }

        return $collection;
    }

}