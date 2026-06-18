<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Text extends Field
    implements Field\Placeholder, Field\DefaultValue, Field\MaxLength, Field\ValueWrapper
{

    use PlaceholderTrait;
    use DefaultValueTrait;
    use MaxLengthTrait;
    use ValueDecoratorTrait;
}