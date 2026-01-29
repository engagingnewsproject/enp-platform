<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Textarea extends Field
    implements Field\Placeholder, Field\DefaultValue, Field\MaxLength, Field\Textarea
{

    use PlaceholderTrait;
    use DefaultValueTrait;
    use MaxLengthTrait;

    public function get_rows(): ?int
    {
        return $this->settings['rows'] && is_numeric($this->settings['rows'])
            ? (int)$this->settings['rows']
            : null;
    }

}