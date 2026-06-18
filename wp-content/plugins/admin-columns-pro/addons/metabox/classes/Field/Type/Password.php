<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field\Field;
use ACA\MetaBox\Field\Placeholder;
use ACA\MetaBox\Field\PlaceholderTrait;

class Password extends Field implements Placeholder
{

    use PlaceholderTrait;
}