<?php

declare(strict_types=1);

namespace ACA\ACF\Column;

use AC\Column\CustomFieldContext;
use AC\Setting\Config;
use AC\Type\TableScreenContext;

class Context extends CustomFieldContext
{

    private array $field_config;

    public function __construct(Config $config, string $label, array $field_config, TableScreenContext $table_context)
    {
        parent::__construct($config, $label, $field_config['type'], $field_config['name'], $table_context);

        $this->field_config = $field_config;
    }

    public function get_field(): array
    {
        return $this->field_config;
    }

    public function get_field_setting($key, $default = null)
    {
        return $this->field_config[$key] ?? $default;
    }

    public function get_field_key(): string
    {
        return (string)$this->get_field_setting('key');
    }

    public function get_field_label(): string
    {
        return (string)$this->get_field_setting('label');
    }

    public function get_field_id(): int
    {
        return (int)$this->get_field_setting('ID', 0);
    }

    public function get_field_parent(): ?int
    {
        $parent = $this->get_field_setting('parent');

        return $parent
            ? (int)$parent
            : null;
    }

}