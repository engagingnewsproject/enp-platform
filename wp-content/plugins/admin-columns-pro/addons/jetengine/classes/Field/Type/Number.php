<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\DefaultValue;
use ACA\JetEngine\Field\DefaultValueTrait;
use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\NumberInput;
use ACA\JetEngine\Field\NumberInputTrait;

final class Number extends Field implements DefaultValue, NumberInput
{

    public const TYPE = 'number';

    use DefaultValueTrait;
    use NumberInputTrait;
}