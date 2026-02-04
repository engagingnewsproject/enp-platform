<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories\WooCommerce;

use AC;
use ACA\ACF;

class OrderCloneFieldFactory
{

    private ACF\FieldFactory $field_factory;

    private ACF\ColumnFactories\WooCommerce\OrderFieldFactory $order_field_factory;

    public function __construct(
        ACF\FieldFactory $field_factory,
        ACF\ColumnFactories\WooCommerce\OrderFieldFactory $order_field_factory
    ) {
        $this->field_factory = $field_factory;
        $this->order_field_factory = $order_field_factory;
    }

    public function create(ACF\Field $field): ?AC\Column\ColumnFactory
    {
        $settings = $field->get_settings();
        $clone_setting = acf_get_field($settings['__key']);

        // Seamless without prefix
        if ($clone_setting['name'] === $settings['name']) {
            $field = $this->create_seamless_clone_field($clone_setting, $settings['label']);

            return $field
                ? $this->order_field_factory->create($field)
                : null;
        }

        return null;
    }

    private function create_seamless_clone_field(array $clone_setting, $label): ?ACF\Field
    {
        $clone_setting['key'] = $clone_setting['name'];
        $clone_setting['label'] = $label;

        return $this->field_factory->create($clone_setting);
    }

}