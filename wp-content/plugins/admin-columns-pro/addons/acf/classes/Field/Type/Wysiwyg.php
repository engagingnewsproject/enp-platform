<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Wysiwyg extends Field
    implements Field\DefaultValue
{

    use DefaultValueTrait;
}