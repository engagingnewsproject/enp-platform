<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class MetaboxValue implements AC\Formatter
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
        $metabox_value = rwmb_get_value($this->field_id, ['object_type' => (string)$this->meta_type], $value->get_id());

        if ($metabox_value === null) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($metabox_value);
    }

}