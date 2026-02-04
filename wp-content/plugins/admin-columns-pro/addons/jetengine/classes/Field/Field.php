<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

class Field
{

    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get_type(): string
    {
        return (string)$this->get('type');
    }

    public function get_title(): string
    {
        return (string)$this->get('title');
    }

    public function get_name(): string
    {
        return (string)$this->get('name');
    }

    public function get_id(): int
    {
        return (int)$this->get('id', 0);
    }

    public function get(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function get_settings(): array
    {
        return $this->settings;
    }

    public function is_required(): bool
    {
        return isset($this->settings['is_required']) && $this->settings['is_required'];
    }

}