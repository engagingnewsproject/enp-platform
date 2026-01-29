<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\DefaultValue;
use ACA\JetEngine\Field\DefaultValueTrait;
use ACA\JetEngine\Field\Field;

final class IconPicker extends Field implements DefaultValue
{

    use DefaultValueTrait;

    public const TYPE = 'iconpicker';

}