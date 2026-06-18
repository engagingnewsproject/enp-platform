<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class Number extends Field\Field implements Field\Placeholder, Field\Numeric
{

    use Field\PlaceholderTrait;
    use Field\NumericTrait;
}