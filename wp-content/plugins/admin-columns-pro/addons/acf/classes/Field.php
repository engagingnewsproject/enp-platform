<?php

declare(strict_types=1);

namespace ACA\ACF;

use InvalidArgumentException;

class Field
{

    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;

        if ( ! self::validate($settings)) {
            throw new InvalidArgumentException('Missing field argument.');
        }
    }

    public static function validate(array $settings): bool
    {
        if ( ! isset($settings['label']) || ! is_string($settings['label'])) {
            return false;
        }

        if ( ! isset($settings['type']) || ! $settings['type'] || ! is_string($settings['type'])) {
            return false;
        }

        if ( ! isset($settings['name']) || ! $settings['name'] || ! is_string($settings['name'])) {
            return false;
        }

        if ( ! isset($settings['key']) || ! $settings['key'] || ! is_string($settings['key'])) {
            return false;
        }

        return true;
    }

    public function is_required(): bool
    {
        return isset($this->settings['required']) && $this->settings['required'];
    }

    public function get_settings(): array
    {
        return $this->settings;
    }

    public function get_label(): string
    {
        return (string)$this->settings['label'];
    }

    public function get_type(): string
    {
        return (string)$this->settings['type'];
    }

    public function get_meta_key(): string
    {
        return (string)$this->settings['name'];
    }

    public function get_hash(): string
    {
        return (string)$this->settings['key'];
    }

    public function is_clone(): bool
    {
        return isset($this->settings['_clone']);
    }

    public function is_deferred_clone(): bool
    {
        return isset($this->settings['ac_clone']);
    }

}