<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait MultipleTrait
{

    public function is_multiple(): bool
    {
        return isset($this->settings['multiple']) && $this->settings['multiple'];
    }

}