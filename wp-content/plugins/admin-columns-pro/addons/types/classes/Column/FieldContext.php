<?php

declare(strict_types=1);

namespace ACA\Types\Column;

use AC\Column\CustomFieldContext;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\Types;

class FieldContext extends CustomFieldContext
{

    private Types\Field $field;

    public function __construct(Config $config, string $label, Types\Field $field, TableScreenContext $table_context)
    {
        parent::__construct($config, $label, $field->get_type(), $field->get_meta_key(), $table_context);

        $this->field = $field;
    }

    public function get_field(): Types\Field
    {
        return $this->field;
    }

    public function get_field_settings(): array
    {
        return $this->field->get_config();
    }

    public function get_field_setting($key, $default = null)
    {
        return $this->field->get_data($key, $default);
    }

}