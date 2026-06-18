<?php

declare(strict_types=1);

namespace ACP\Editing\View\CompositeField;

use AC\Type\ToggleOptions;

class CheckboxField extends CompositeField
{

    public function __construct(string $name, string $label, ToggleOptions $options, string $toggle_label = '')
    {
        parent::__construct($name, $label, 'checkbox');

        $this->set('toggle_label', $toggle_label);
        $this->set_options($options);
    }

    public function set_options(ToggleOptions $options): self
    {
        $this->set('options', [
            [
                'value' => $options->get_disabled()->get_value(),
                'label' => $options->get_disabled()->get_label(),
            ],
            [
                'value' => $options->get_enabled()->get_value(),
                'label' => $options->get_enabled()->get_label(),
            ],
        ]);

        return $this;
    }
}