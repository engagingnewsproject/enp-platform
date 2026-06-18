<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class DateTime extends Field\Field implements Field\DateFormat
{

    public function get_date_format(): string
    {
        if ($this->is_table_storage()) {
            return $this->is_timestamp() ? 'U' : 'Y-m-d H:i:s';
        }

        return $this->is_timestamp() ? 'U' : 'Y-m-d H:i';
    }

    public function is_timestamp(): bool
    {
        $value = $this->settings['timestamp'] ?? false;

        return in_array($value, [true, 'true', 1], true);
    }

}