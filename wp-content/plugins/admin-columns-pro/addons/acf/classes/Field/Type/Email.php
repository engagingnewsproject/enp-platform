<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Email extends Field
    implements Field\Placeholder, Field\DefaultValue, Field\ValueWrapper
{

    use PlaceholderTrait;
    use ValueDecoratorTrait;
    use DefaultValueTrait;
}