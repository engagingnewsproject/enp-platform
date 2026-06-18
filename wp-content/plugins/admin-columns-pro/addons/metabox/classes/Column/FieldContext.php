<?php

declare(strict_types=1);

namespace ACA\MetaBox\Column;

use AC\Column\CustomFieldContext;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\MetaBox;

class FieldContext extends CustomFieldContext
{

    private MetaBox\Field\Field $field;

    public function __construct(
        Config $config,
        string $label,
        MetaBox\Field\Field $field,
        TableScreenContext $table_context
    ) {
        parent::__construct($config, $label, $field->get_type(), $field->get_id(), $table_context);

        $this->field = $field;
    }

    public function get_field(): MetaBox\Field\Field
    {
        return $this->field;
    }

    public function is_cloneable(): bool
    {
        return $this->field->is_cloneable();
    }

    public function get_field_setttings(): array
    {
        return $this->field->get_settings();
    }

    public function get_field_setting($key, $default = null)
    {
        return $this->field->get_setting($key, $default);
    }

}