<?php

declare(strict_types=1);

namespace ACP\Storage;

final class EncodedContext
{

    private array $encoded_data;

    private array $attributes;

    public function __construct(array $encoded_data, array $attributes = [])
    {
        $this->encoded_data = $encoded_data;
        $this->attributes = $attributes;
    }

    public function get_encoded_data(): array
    {
        return $this->encoded_data;
    }

    public function with_attribute(string $key, $value): self
    {
        $attributes = $this->attributes;
        $attributes[$key] = $value;

        return new self($this->encoded_data, $attributes);
    }

    public function has_attribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function get_attribute(string $key, $default = null)
    {
        if ( ! $this->has_attribute($key)) {
            return $default;
        }

        return $this->attributes[$key];
    }

}