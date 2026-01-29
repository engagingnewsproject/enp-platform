<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;
use ACA\ACF\FieldFactory;

class Repeater extends Field implements Field\Subfields
{

    private FieldFactory $field_factory;

    public function __construct(FieldFactory $field_factory, array $settings)
    {
        parent::__construct($settings);

        $this->field_factory = $field_factory;
    }

    public function get_sub_fields(): array
    {
        return isset($this->settings['sub_fields']) && is_array($this->settings['sub_fields'])
            ? $this->settings['sub_fields']
            : [];
    }

    public function get_sub_field($key): ?Field
    {
        foreach ($this->get_sub_fields() as $field) {
            if ($field['key'] === $key) {
                return $this->field_factory->create($field);
            }
        }

        return null;
    }

}