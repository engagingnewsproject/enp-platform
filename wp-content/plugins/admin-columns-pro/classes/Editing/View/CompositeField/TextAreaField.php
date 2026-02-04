<?php

declare(strict_types=1);

namespace ACP\Editing\View\CompositeField;

class TextAreaField extends CompositeField
{

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, 'textarea');
    }

    public function set_rows(int $rows): self
    {
        $this->set('rows', $rows);

        return $this;
    }
}