<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait ChoicesTrait
{

    public function get_choices(): array
    {
        return isset($this->settings['choices']) && $this->settings['choices']
            ? (array)$this->settings['choices']
            : [];
    }

}