<?php

declare(strict_types=1);

namespace ACA\JetEngine\ColumnFactory\Meta;

use AC\Column\Context;
use AC\Formatter;
use AC\Formatter\Collection\Separator;
use AC\Formatter\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\JetEngine;
use ACA\JetEngine\ConditionalFormatting\FormattableConfigFactory;
use ACA\JetEngine\Editing;
use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\Type;
use ACA\JetEngine\Search;
use ACA\JetEngine\Service\ColumnGroups;
use ACA\JetEngine\Setting\FieldSettingFactory;
use ACA\JetEngine\Sorting;
use ACA\JetEngine\Value\ValueFormatterFactory;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;

class FieldFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\Column\FormatterSettingsTrait;

    private string $column_type;

    private string $label;

    protected Field $field;

    protected TableScreenContext $table_context;

    protected FieldSettingFactory $field_setting_factory;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date;

    protected ValueFormatterFactory $value_formatter_factory;

    private Sorting\ModelFactory $sorting_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        TableScreenContext $table_context,
        FieldSettingFactory $field_setting_factory,
        ValueFormatterFactory $value_formatter_factory,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date,
        Sorting\ModelFactory $sorting_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->field = $field;
        $this->table_context = $table_context;
        $this->field_setting_factory = $field_setting_factory;
        $this->filtering_date = $filtering_date;
        $this->value_formatter_factory = $value_formatter_factory;
        $this->sorting_factory = $sorting_factory;
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    protected function get_group(): ?string
    {
        return ColumnGroups::JET_ENGINE;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return (new Editing\MetaServiceFactory())->create($this->field, $this->table_context->get_meta_type());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new Search\ComparisonFactory())->create($this->field, $this->table_context);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->sorting_factory->create(
            $this->field,
            $this->table_context
        );
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);

        if ($this->field->get_type() === Type\Date::TYPE || $this->field->get_type() === Type\DateTime::TYPE) {
            $builder->set_search(null, $this->filtering_date);
        }

        return $builder;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = new ComponentCollection();

        foreach ($this->field_setting_factory->create($this->field) as $setting) {
            $settings->add($setting->create($config));
        }

        return $settings;
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return (new FormattableConfigFactory())->create($this->field, $this->get_base_formatter());
    }

    private function get_base_formatter(): Formatter
    {
        return new Meta($this->table_context->get_meta_type(), $this->field->get_name());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $setting_formatters = $this->get_formatters_from_settings($this->get_settings($config));
        $formatters = $this->value_formatter_factory->create($this->field, $setting_formatters);

        $formatters->prepend($this->get_base_formatter());
        $formatters->add(Separator::create_from_config($config));

        return $formatters;
    }

    protected function get_context(Config $config): Context
    {
        return new JetEngine\Column\FieldContext(
            $config,
            $this->get_label(),
            $this->field,
            $this->table_context
        );
    }

}