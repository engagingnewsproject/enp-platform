<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class Date extends Field\Field implements Field\DateFormat
{

    public function get_date_format(): string
    {
        return $this->is_timestamp() ? 'U' : 'Y-m-d';
    }

    public function is_timestamp(): bool
    {
        $value = $this->settings['timestamp'] ?? false;

        return in_array($value, [true, 'true', 1], true);
    }

}