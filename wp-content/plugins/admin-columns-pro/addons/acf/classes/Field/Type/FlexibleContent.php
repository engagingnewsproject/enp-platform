<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class FlexibleContent extends Field
{

    public function get_layouts(): array
    {
        return isset($this->settings['layouts']) && is_array($this->settings['layouts'])
            ? $this->settings['layouts']
            : [];
    }

}