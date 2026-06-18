<?php

declare(strict_types=1);

namespace ACA\JetEngine\ColumnFactory\Meta;

use AC;
use AC\Formatter\Aggregate;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use ACA\JetEngine\Field;
use ACA\JetEngine\Setting\ComponentFactory\RepeaterField;
use ACA\JetEngine\Value;
use ACP\Column\FormatterSettingsTrait;

class Repeater extends FieldFactory
{

    use FormatterSettingsTrait;

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = new ComponentCollection();

        if ($this->field instanceof Field\Type\Repeater) {
            $settings->add(
                (new RepeaterField($this->field))->create($config)
            );
        }

        $sub_field = $this->get_sub_field($config);

        if ($sub_field) {
            $sub_settings = $this->field_setting_factory->create($sub_field);
            foreach ($sub_settings as $sub_setting) {
                $settings->add($sub_setting->create($config));
            }
        }

        return $settings;
    }

    private function get_sub_field_key(Config $config): string
    {
        return $config->get('sub_field', '');
    }

    protected function get_sub_field(Config $config): ?Field\Field
    {
        foreach ($this->field->get_repeated_fields() as $field) {
            if ($this->get_sub_field_key($config) === $field->get_name()) {
                return $field;
            }
        }

        return null;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new AC\Formatter\Meta($this->table_context->get_meta_type(), $this->field->get_name()),
            new Value\Formatter\Repeater($this->get_sub_field_key($config)),
        ]);

        $subfield = $this->get_sub_field($config);

        if ($subfield) {
            $formatters->add(
                new Aggregate(
                    $this->value_formatter_factory->create(
                        $subfield,
                        $this->get_formatters_from_settings($this->get_settings($config))
                    )
                )
            );
        }

        $formatters->add(new AC\Formatter\Collection\Separator('<br>'));

        return $formatters;
    }
}