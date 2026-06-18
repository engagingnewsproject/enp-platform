<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class AutoComplete extends Field\Field implements Field\Choices
{

    public function get_choices(): array
    {
        return $this->is_ajax()
            ? []
            : (array)$this->settings['options'];
    }

    public function is_ajax(): bool
    {
        return filter_var($this->settings['options'], FILTER_VALIDATE_URL);
    }
}