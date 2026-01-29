<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field\Choices;
use ACA\MetaBox\Field\Field;

class CheckboxList extends Field implements Choices
{

    public function get_choices(): array
    {
        return isset($this->settings['options'])
            ? (array)$this->settings['options']
            : [];
    }

}