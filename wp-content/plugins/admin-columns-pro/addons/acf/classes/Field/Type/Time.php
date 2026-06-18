<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Time extends Field implements Field\SaveFormat
{

    public function get_display_format(): string
    {
        return (string)$this->settings['display_format'];
    }

    public function get_save_format(): string
    {
        return 'H:i:s';
    }

}