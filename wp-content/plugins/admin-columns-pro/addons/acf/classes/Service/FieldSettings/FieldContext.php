<?php

declare(strict_types=1);

namespace ACA\ACF\Service\FieldSettings;

class FieldContext
{

    private string $table_id;

    private string $field_key;

    private string $field_label;

    public function __construct(string $table_id, string $field_key, string $field_label)
    {
        $this->table_id = $table_id;
        $this->field_key = $field_key;
        $this->field_label = $field_label;
    }

    public function get_table_id(): string
    {
        return $this->table_id;
    }

    public function get_field_key(): string
    {
        return $this->field_key;
    }

    public function get_field_label(): string
    {
        return $this->field_label;
    }

}
