<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class ImageSelect extends Field\Field implements Field\Choices, Field\Multiple
{

    use Field\MultipleTrait;

    public function get_choices(): array
    {
        return isset($this->settings['options'])
            ? (array)$this->settings['options']
            : [];
    }
}