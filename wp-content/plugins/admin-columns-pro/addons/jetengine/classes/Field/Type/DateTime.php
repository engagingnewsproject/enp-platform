<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\TimeStamp;
use ACA\JetEngine\Field\TimestampTrait;

final class DateTime extends Field implements TimeStamp
{

    public const TYPE = 'datetime-local';

    use TimestampTrait;
}