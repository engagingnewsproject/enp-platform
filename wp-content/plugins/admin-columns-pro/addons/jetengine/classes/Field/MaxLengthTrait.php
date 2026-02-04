<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

trait MaxLengthTrait
{

    public function get_maxlength(): int
    {
        return $this->has_maxlength()
            ? (int)$this->settings['max_length']
            : 0;
    }

    public function has_maxlength(): bool
    {
        return isset($this->settings['max_length']) && is_numeric($this->settings['max_length']);
    }

}