<?php

declare(strict_types=1);

namespace ACP\Editing\View\CompositeField;

class SelectField extends CompositeField
{

    public function __construct(string $name, string $label, array $options)
    {
        parent::__construct($name, $label, 'select');

        $this->set_options($options);
    }

    public function set_options(array $options): self
    {
        $mapped_options = [];

        foreach ($options as $value => $label) {
            $mapped_options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        $this->set('options', $mapped_options);

        return $this;
    }
}