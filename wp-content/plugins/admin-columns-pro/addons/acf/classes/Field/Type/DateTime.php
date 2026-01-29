<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class DateTime extends Field
    implements Field\Date, Field\SaveFormat
{

    public function get_display_format(): string
    {
        return (string)$this->settings['display_format'];
    }

    public function get_first_day(): int
    {
        return (int)$this->settings['first_day'];
    }

    public function get_save_format(): string
    {
        return 'Y-m-d H:i:s';
    }

}