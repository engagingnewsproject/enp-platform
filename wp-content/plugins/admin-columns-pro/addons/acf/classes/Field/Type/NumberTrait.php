<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait NumberTrait
{

    public function get_min(): ?int
    {
        return isset($this->settings['min']) && is_numeric($this->settings['min'])
            ? (int)$this->settings['min']
            : null;
    }

    public function get_max(): ?int
    {
        return isset($this->settings['max']) && is_numeric($this->settings['max'])
            ? (int)$this->settings['max']
            : null;
    }

    public function get_step(): string
    {
        return isset($this->settings['step']) && $this->settings['step']
            ? (string)$this->settings['step']
            : 'any';
    }

}