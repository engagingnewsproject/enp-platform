<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait ChoicesTrait
{

    public function get_choices(): array
    {
        if (empty($this->settings['choices'])) {
            return [];
        }

        $choices = (array)$this->settings['choices'];

        // Flatten optgroup arrays: ['Group' => ['val' => 'Label', ...], ...] → ['val' => 'Label', ...]
        $flattened = [];

        foreach ($choices as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $sub_key => $sub_value) {
                    $flattened[$sub_key] = $sub_value;
                }
            } else {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }

}