<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait PlaceholderTrait
{

    public function get_placeholder(): string
    {
        return (string)$this->settings['placeholder'];
    }

}