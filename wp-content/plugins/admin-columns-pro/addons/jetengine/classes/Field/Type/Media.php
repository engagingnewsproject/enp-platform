<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\ValueFormat;

final class Media extends Field implements ValueFormat
{

    public const TYPE = 'media';

    public function get_value_format(): string
    {
        return $this->settings['value_format'] ?? ValueFormat::FORMAT_ID;
    }

}