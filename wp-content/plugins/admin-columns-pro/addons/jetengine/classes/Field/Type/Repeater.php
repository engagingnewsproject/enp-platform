<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\FieldFactory;

final class Repeater extends Field
{

    public const TYPE = 'repeater';

    /**
     * @return Field[];
     */
    public function get_repeated_fields(): array
    {
        $field_factory = new FieldFactory();
        $settings = [];

        foreach ($this->settings['repeater-fields'] as $field_settings) {
            $settings[] = $field_factory->create($field_settings);
        }

        return array_filter($settings);
    }

}