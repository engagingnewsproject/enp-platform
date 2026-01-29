<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory;

use AC\Column\Context;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\Pods;
use ACA\Pods\Field;
use ACA\Pods\Value\Formatter\PodsFieldRaw;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class FieldFactory extends AdvancedColumnFactory
{

    private string $column_type;

    private string $label;

    protected Field $field;

    protected TableScreenContext $table_context;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        TableScreenContext $table_context
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->field = $field;
        $this->table_context = $table_context;
    }

    protected function get_group(): string
    {
        return 'pods';
    }

    public function get_label(): string
    {
        return $this->label;
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return $this->get_base_formatters($config)->merge(parent::get_formatters($config));
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new PodsFieldRaw($this->field, true),
        ]);
    }

    protected function get_post_type(): ?string
    {
        return $this->table_context->has_post_type()
            ? (string)$this->table_context->get_post_type()
            : null;
    }

    protected function get_context(Config $config): Context
    {
        return new Pods\Column\FieldContext(
            $config,
            $this->get_label(),
            $this->field->get_field(),
            $this->table_context
        );
    }

}