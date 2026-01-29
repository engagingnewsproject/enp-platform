<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

trait NumberInputTrait
{

    public function has_step(): bool
    {
        return isset($this->settings['step_value']) && is_numeric($this->settings['step_value']);
    }

    public function get_step(): string
    {
        return $this->has_step() ? (string)$this->settings['step_value'] : '';
    }

    public function has_min_value(): bool
    {
        return isset($this->settings['min_value']) && is_numeric($this->settings['min_value']);
    }

    public function get_min_value(): string
    {
        return $this->has_min_value() ? (string)$this->settings['min_value'] : '';
    }

    public function has_max_value(): bool
    {
        return isset($this->settings['max_value']) && is_numeric($this->settings['max_value']);
    }

    public function get_max_value(): string
    {
        return $this->has_max_value() ? (string)$this->settings['max_value'] : '';
    }

}