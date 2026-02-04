<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Range extends Field implements Field\Number, Field\DefaultValue, Field\ValueWrapper
{

    use DefaultValueTrait;
    use ValueDecoratorTrait;
    use NumberTrait;

    public function get_step(): string
    {
        return isset($this->settings['step']) && $this->settings['step']
            ? (string)$this->settings['step']
            : '1';
    }
}