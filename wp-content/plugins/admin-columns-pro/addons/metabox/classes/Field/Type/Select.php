<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class Select extends Field\Field implements Field\Choices, Field\Multiple, Field\Placeholder
{

    use Field\ChoicesTrait;
    use Field\MultipleTrait;
    use Field\PlaceholderTrait;
}