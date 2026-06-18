<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field;

trait MultipleTrait
{

    public function is_multiple(): bool
    {
        $multiple = $this->settings['multiple'];

        return in_array($multiple, [true, 'true', 1], true);
    }

}