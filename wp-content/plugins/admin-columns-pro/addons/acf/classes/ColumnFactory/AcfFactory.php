<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory;

use AC\Column\Context;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\ACF;
use ACA\ACF\Field;
use ACA\ACF\Service\ColumnGroup;
use ACA\ACF\Setting\FieldComponentFactory;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class AcfFactory extends ACP\Column\AdvancedColumnFactory
{

    private string $column_type;

    private string $label;

    protected Field $field;

    protected TableScreenContext $table_context;

    protected FieldComponentFactory $component_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        FieldComponentFactory $component_factory,
        TableScreenContext $table_context
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->column_type = $column_type;
        $this->label = $label;
        $this->field = $field;
        $this->component_factory = $component_factory;
        $this->table_context = $table_context;
    }

    protected function get_group(): string
    {
        $parent = $this->field->get_settings()['parent'] ?? null;

        if ( ! $parent) {
            return ColumnGroup::SLUG;
        }

        if (is_numeric($parent)) {
            return ColumnGroup::SLUG . $parent;
        }

        $group = acf_get_field_group($parent);

        if ( ! $group) {
            return ColumnGroup::SLUG;
        }

        return ColumnGroup::SLUG . $group['ID'];
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $components = new ComponentCollection();

        foreach ($this->component_factory->create($this->field) as $component) {
            $components->add($component->create($config));
        }

        return $components;
    }

    protected function get_context(Config $config): Context
    {
        return new ACF\Column\Context(
            $config,
            $this->get_label(),
            $this->field->get_settings(),
            $this->table_context
        );
    }

}