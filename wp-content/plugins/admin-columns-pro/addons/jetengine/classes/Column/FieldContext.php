<?php

declare(strict_types=1);

namespace ACA\JetEngine\Column;

use AC\Column\CustomFieldContext;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\JetEngine;

class FieldContext extends CustomFieldContext
{

    private JetEngine\Field\Field $field;

    public function __construct(
        Config $config,
        string $label,
        JetEngine\Field\Field $field,
        TableScreenContext $table_context
    ) {
        parent::__construct($config, $label, $field->get_type(), $field->get_name(), $table_context);

        $this->field = $field;
    }

    public function get_field(): JetEngine\Field\Field
    {
        return $this->field;
    }

    public function get_field_settings(): array
    {
        return $this->field->get_settings();
    }

    public function get_field_setting(string $var)
    {
        return $this->field->get($var);
    }

    public function get_field_title(): string
    {
        return $this->field->get_title();
    }

}