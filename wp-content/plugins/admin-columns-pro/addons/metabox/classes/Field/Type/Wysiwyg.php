<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class Wysiwyg extends Field\Field
{

    public function store_raw(): bool
    {
        return $this->check_true_value('raw');
    }
}