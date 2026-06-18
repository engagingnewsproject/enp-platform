<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field\Choices;
use ACA\MetaBox\Field\ChoicesTrait;
use ACA\MetaBox\Field\Field;

class Radio extends Field implements Choices
{

    use ChoicesTrait;
}