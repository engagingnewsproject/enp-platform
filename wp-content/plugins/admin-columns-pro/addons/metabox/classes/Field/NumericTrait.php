<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field;

trait NumericTrait
{

    public function get_max(): ?float
    {
        return $this->settings['max'] ? (float)$this->settings['max'] : null;
    }

    public function get_min(): ?float
    {
        return $this->settings['min'] ? (float)$this->settings['min'] : null;
    }

    public function get_step(): string
    {
        return (string)$this->settings['step'];
    }

}