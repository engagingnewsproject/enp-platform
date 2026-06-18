<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\DefaultValue;
use ACA\JetEngine\Field\DefaultValueTrait;
use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\MaxLength;
use ACA\JetEngine\Field\MaxLengthTrait;

final class Textarea extends Field implements MaxLength, DefaultValue
{

    public const TYPE = 'textarea';

    use DefaultValueTrait;
    use MaxLengthTrait;
}