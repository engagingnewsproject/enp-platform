<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use ACA\ACF;
use ACA\ACF\Field;

class CloneFieldResolver
{

    private ACF\FieldFactory $field_factory;

    public function __construct(ACF\FieldFactory $field_factory)
    {
        $this->field_factory = $field_factory;
    }

    public function resolve(Field $field): ?Field
    {
        $settings = $field->get_settings();
        $clone_setting = acf_get_field($settings['__key']);

        // Seamless without prefix
        if ($clone_setting && $clone_setting['name'] === $settings['name']) {
            $clone_setting['key'] = $clone_setting['name'];
            $clone_setting['label'] = $settings['label'];

            return $this->field_factory->create($clone_setting);
        }

        // Prefixed clone
        $explode = explode('_', $settings['key']);

        // Grouped prefixed
        if (count($explode) === 2) {
            $settings['key'] = $settings['_clone'] . '_' . $settings['key'];
        }

        foreach (['_clone', '_name', '_valid', '__name', '__label', '__key'] as $key) {
            unset($settings[$key]);
        }

        $settings['ac_clone'] = true;

        return $this->field_factory->create($settings);
    }

}
