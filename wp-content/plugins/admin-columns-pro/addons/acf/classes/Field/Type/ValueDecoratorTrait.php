<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait ValueDecoratorTrait
{

    public function get_append(): string
    {
        return (string)$this->settings['append'];
    }

    public function get_prepend(): string
    {
        return (string)$this->settings['prepend'];
    }

}