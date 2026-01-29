<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field;

use LogicException;
use MetaBox;

class Field
{

    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get_id(): string
    {
        return (string)$this->settings['id'];
    }

    public function get_type(): string
    {
        return (string)$this->settings['type'];
    }

    public function get_name(): string
    {
        return (string)$this->settings['name'];
    }

    public function get_setting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function get_settings(): array
    {
        return $this->settings;
    }

    protected function check_true_value(string $key): bool
    {
        $value = $this->settings[$key] ?? false;

        return in_array($value, [true, 'true', 1], true);
    }

    public function is_cloneable(): bool
    {
        return $this->check_true_value('clone');
    }

    public function is_table_storage(): bool
    {
        return $this->settings['storage'] instanceof MetaBox\CustomTable\Storage;
    }

    public function get_table_storage(): MetaBox\CustomTable\Storage
    {
        if ( ! $this->is_table_storage()) {
            throw new LogicException(sprintf('Field %s is not table storage', $this->get_id()));
        }

        return $this->settings['storage'];
    }

    public function get_table_storage_table(): string
    {
        return $this->get_table_storage()->table;
    }

}