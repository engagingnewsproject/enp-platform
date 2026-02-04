<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field\Choices;
use ACA\MetaBox\Field\ChoicesTrait;
use ACA\MetaBox\Field\Field;
use ACA\MetaBox\Field\Multiple;
use ACA\MetaBox\Field\MultipleTrait;
use ACA\MetaBox\Field\Placeholder;
use ACA\MetaBox\Field\PlaceholderTrait;

class SelectAdvanced extends Field implements Choices, Multiple, Placeholder
{

    use ChoicesTrait;
    use PlaceholderTrait;
    use MultipleTrait;
}