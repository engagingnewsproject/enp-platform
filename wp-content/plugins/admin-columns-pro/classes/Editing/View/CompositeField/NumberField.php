<?php

declare(strict_types=1);

namespace ACP\Editing\View\CompositeField;

class NumberField extends CompositeField
{

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, 'number');
    }

    public function set_min(int $min): self
    {
        $this->set('min', $min);

        return $this;
    }

    public function set_max(int $max): self
    {
        $this->set('max', $max);

        return $this;
    }

}