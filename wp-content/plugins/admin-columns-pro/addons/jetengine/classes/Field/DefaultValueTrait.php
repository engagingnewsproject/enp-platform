<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

trait DefaultValueTrait
{

    public function has_default_value(): bool
    {
        return isset($this->settings['default_val']) && $this->settings['default_val'] !== '';
    }

    public function get_default_value(): ?string
    {
        return $this->has_default_value()
            ? (string)$this->settings['default_val']
            : null;
    }

}