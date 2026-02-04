<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait MaxLengthTrait
{

    public function get_max_length(): ?int
    {
        return isset($this->settings['maxlength']) && is_numeric($this->settings['maxlength'])
            ? (int)$this->settings['maxlength']
            : null;
    }

}