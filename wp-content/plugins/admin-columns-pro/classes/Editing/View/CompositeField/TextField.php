<?php

declare(strict_types=1);

namespace ACP\Editing\View\CompositeField;

class TextField extends CompositeField
{

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, 'text');
    }

    public function set_placeholder(string $placeholder): self
    {
        $this->set('placeholder', $placeholder);

        return $this;
    }

}