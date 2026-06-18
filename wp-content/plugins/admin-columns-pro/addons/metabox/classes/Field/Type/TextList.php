<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class TextList extends Field\Field implements Field\Choices
{

    use Field\ChoicesTrait;
}