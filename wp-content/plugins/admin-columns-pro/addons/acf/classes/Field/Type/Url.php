<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Url extends Field
    implements Field\Placeholder, Field\DefaultValue
{

    use PlaceholderTrait;
    use DefaultValueTrait;
}