<?php

declare(strict_types=1);

namespace ACA\Types;

class Field
{

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get_id(): string
    {
        return (string)$this->config['id'];
    }

    public function get_label(): string
    {
        return (string)($this->config['name'] ?? '');
    }

    public function get_type(): string
    {
        return (string)($this->config['type'] ?? '');
    }

    public function get_meta_key(): string
    {
        return (string)($this->config['meta_key'] ?? '');
    }

    public function is_repeatable(): bool
    {
        return isset($this->config['data']['repetitive']) && '1' === $this->config['data']['repetitive'];
    }

    public function get_config(): array
    {
        return $this->config;
    }

    public function get_data(string $key, $default = null)
    {
        return $this->config['data'][$key] ?? $default;
    }

}