<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\Meta;

use AC\Column\Context;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\MetaBox;
use ACA\MetaBox\Field;
use ACA\MetaBox\Setting;
use ACA\MetaBox\Value;
use ACA\MetaBox\Value\Formatter\FileNames;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\FormatterSettingsTrait;

class GroupFactory extends AdvancedColumnFactory
{

    use FormatterSettingsTrait;

    private string $column_type;

    private string $label;

    protected Field\Type\Group $field;

    protected TableScreenContext $table_context;

    protected Setting\FieldComponentFactory $setting_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        TableScreenContext $table_context,
        Field\Type\Group $field,
        Setting\FieldComponentFactory $setting_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->table_context = $table_context;
        $this->field = $field;
        $this->setting_factory = $setting_factory;
    }

    public function get_sub_field(Config $config): ?Field\Field
    {
        foreach ($this->field->get_sub_fields() as $field) {
            if ($field->get_id() === $config->get('group_field', '')) {
                return $field;
            }
        }

        return null;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = parent::get_settings($config);
        $settings->add((new Setting\ComponentFactory\GroupField($this->field))->create($config));
        $sub_field = $this->get_sub_field($config);

        if ($sub_field) {
            foreach ($this->setting_factory->create($sub_field) as $component) {
                $settings->add($component->create($config));
            }
        }

        return $settings;
    }

    protected function get_group(): ?string
    {
        return 'metabox';
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        $sub_field = $this->get_sub_field($config);

        if ( ! $sub_field) {
            return null;
        }

        switch ($sub_field->get_type()) {
            case MetaBox\MetaboxFieldTypes::CHECKBOX:
                return $this->get_raw_formatter($config);
            case MetaBox\MetaboxFieldTypes::IMAGE_ADVANCED:
            case MetaBox\MetaboxFieldTypes::IMAGE:
            case MetaBox\MetaboxFieldTypes::SINGLE_IMAGE:
                return $this->get_raw_formatter($config)->with_formatter(new FileNames());
            default:
                return parent::get_export($config);
        }
    }

    protected function get_raw_formatter(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Value\Formatter\GroupField(
                $this->table_context->get_meta_type(),
                $this->field->get_id(),
                $config->get('group_field', '')
            ),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = $this->get_raw_formatter($config);

        $sub_field = $this->get_sub_field($config);
        if ($sub_field) {
            $additional_formatters = (new Value\ValueFormatterFactory())->create(
                $this->get_formatters_from_settings($this->get_settings($config)),
                $sub_field,
                $config
            );
            foreach ($additional_formatters as $formatter) {
                $formatters->add($formatter);
            }
        }

        return $formatters;
    }

    protected function get_context(Config $config): Context
    {
        return new MetaBox\Column\FieldContext(
            $config,
            $this->get_label(),
            $this->field,
            $this->table_context
        );
    }

}