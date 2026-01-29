<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\MetaType;
use AC\Type\Value;
use ACA\Types\Field;

class TypesRenderValue implements Formatter
{

    private $field;

    private $meta_type;

    public function __construct(MetaType $meta_type, Field $field)
    {
        $this->field = $field;
        $this->meta_type = $meta_type;
    }

    public function format(Value $value)
    {
        switch ($this->meta_type->get()) {
            case MetaType::POST:
                return $value->with_value(
                    types_render_field($this->field->get_id(), ['separator' => ', ', 'id' => $value->get_id()])
                );
            case MetaType::USER:
                return $value->with_value(
                    types_render_usermeta($this->field->get_id(), ['separator' => ', ', 'user_id' => $value->get_id()])
                );
            case MetaType::TERM:
                return $value->with_value(
                    types_render_termmeta($this->field->get_id(), ['separator' => ', ', 'term_id' => $value->get_id()])
                );
        }

        throw ValueNotFoundException::from_id($value->get_id());
    }
}