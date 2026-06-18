<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait DefaultValueTrait
{

    public function get_default_value(): string
    {
        return isset($this->settings['default_value'])
            ? (string)$this->settings['default_value']
            : '';
    }

}