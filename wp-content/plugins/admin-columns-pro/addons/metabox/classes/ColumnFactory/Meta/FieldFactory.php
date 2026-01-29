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
use ACA\MetaBox\ConditionalFormatting\FormattableConfigFactory;
use ACA\MetaBox\Editing;
use ACA\MetaBox\Export;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Search;
use ACA\MetaBox\Setting\FieldComponentFactory;
use ACA\MetaBox\Sorting;
use ACA\MetaBox\Value;
use ACA\MetaBox\Value\Formatter\MetaboxValue;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;

class FieldFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\Column\FormatterSettingsTrait;

    private string $column_type;

    private string $label;

    protected Field\Field $field;

    protected TableScreenContext $table_context;

    private Editing\ServiceFactory $editing_factory;

    private Sorting\ModelFactory $sorting_factory;

    private FormattableConfigFactory $conditional_format_factory;

    private FieldComponentFactory $component_factory_factory;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $date_filter_factory;

    private Search\ComparisonFactory $search_comparison_factory;

    private Value\ValueFormatterFactory $value_formatter_factory;

    private Export\FormatterFactory $export_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field\Field $field,
        TableScreenContext $table_context,
        Editing\ServiceFactory $editing_factory,
        Export\FormatterFactory $export_factory,
        Sorting\ModelFactory $sorting_factory,
        FormattableConfigFactory $conditional_format_factory,
        FieldComponentFactory $component_factory_factory,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $date_filter_factory,
        Search\ComparisonFactory $search_comparison_factory,
        Value\ValueFormatterFactory $value_formatter_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->field = $field;
        $this->table_context = $table_context;
        $this->editing_factory = $editing_factory;
        $this->sorting_factory = $sorting_factory;
        $this->conditional_format_factory = $conditional_format_factory;
        $this->component_factory_factory = $component_factory_factory;
        $this->date_filter_factory = $date_filter_factory;
        $this->search_comparison_factory = $search_comparison_factory;
        $this->value_formatter_factory = $value_formatter_factory;
        $this->export_factory = $export_factory;
    }

    protected function get_group(): string
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

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = new ComponentCollection([]);

        foreach ($this->component_factory_factory->create($this->field) as $factory) {
            $settings->add($factory->create($config));
        }

        return $settings;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->export_factory->create(
            $this->field,
            $this->get_base_formatters(),
            $this->get_formatters($config)
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return $this->editing_factory->create($this->field, $this->table_context);
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return $this->conditional_format_factory->create($this->field, $this->get_base_formatters());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->sorting_factory->create($this->field, $this->table_context, $config);
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);

        if (in_array($this->field->get_type(), [MetaboxFieldTypes::DATE, MetaboxFieldTypes::DATETIME], true)) {
            $builder->set_search(null, $this->date_filter_factory);
        }

        return $builder;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return $this->search_comparison_factory->create($this->field, $this->table_context);
    }

    protected function get_base_formatters(): FormatterCollection
    {
        return new FormatterCollection([
            new MetaboxValue($this->table_context->get_meta_type(), $this->field->get_id()),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return $this->get_base_formatters()->merge(
            $this->value_formatter_factory->create(
                $this->get_formatters_from_settings($this->get_settings($config)),
                $this->field,
                $config
            )
        );
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