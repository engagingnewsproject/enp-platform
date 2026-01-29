<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use ACA\ACF;
use ACA\ACF\Field;

class CloneFieldFactory
{

    private ACF\FieldFactory $field_factory;

    private FieldFactory $field_factory_factory;

    public function __construct(
        FieldFactory $field_factory_factory,
        ACF\FieldFactory $field_factory
    ) {
        $this->field_factory = $field_factory;
        $this->field_factory_factory = $field_factory_factory;
    }

    public function create(AC\Type\TableScreenContext $table_context, Field $field): ?AC\Column\ColumnFactory
    {
        if ( ! $field->is_clone()) {
            return null;
        }

        $settings = $field->get_settings();
        $clone_setting = acf_get_field($settings['__key']);

        // Seamless without prefix
        if ($clone_setting && $clone_setting['name'] === $settings['name']) {
            $field = $this->create_seamless_clone_field($clone_setting, $settings['label']);

            return $field
                ? $this->field_factory_factory->create($table_context, $field)
                : null;
        }

        $explode = explode('_', $settings['key']);

        // Grouped prefixed
        if (count($explode) === 2) {
            $settings['key'] = $settings['_clone'] . '_' . $settings['key'];
        }

        $field = $this->create_prefixed_clone($settings);

        return $field
            ? $this->field_factory_factory->create($table_context, $field)
            : null;
    }

    private function create_seamless_clone_field(array $clone_setting, $label): ?Field
    {
        $clone_setting['key'] = $clone_setting['name'];
        $clone_setting['label'] = $label;

        return $this->field_factory->create($clone_setting);
    }

    private function create_prefixed_clone(array $settings): ?Field
    {
        foreach (['_clone', '_name', '_valid', '__name', '__label', '__key'] as $key) {
            unset($settings[$key]);
        }

        $settings['ac_clone'] = true;

        return $this->field_factory->create($settings);
    }

}