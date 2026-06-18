<?php

declare(strict_types=1);

namespace ACP\Editing\View\CompositeField;

class CompositeField
{

    protected array $args = [];

    public function __construct(string $name, string $label, string $type)
    {
        $this->set('name', $name);
        $this->set('label', $label);
        $this->set('type', $type);
    }

    protected function set(string $key, $value): self
    {
        $this->args[$key] = $value;

        return $this;
    }

    public function get_args(): array
    {
        return $this->args;
    }

    public function set_required(bool $required = true): self
    {
        $this->set('required', $required);

        return $this;
    }

    public function set_class(string $class): self
    {
        return $this->set('class', $class);
    }
}