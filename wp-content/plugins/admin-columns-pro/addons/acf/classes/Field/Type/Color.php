<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Color extends Field implements Field\DefaultValue
{

    use DefaultValueTrait;

    public function has_opacity(): bool
    {
        return (bool)($this->settings['enable_opacity'] ?? false);
    }

}