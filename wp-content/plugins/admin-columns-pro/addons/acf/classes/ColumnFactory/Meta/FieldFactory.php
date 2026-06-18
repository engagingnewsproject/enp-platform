<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\Meta;

use AC\Formatter;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactory\AcfFactory;
use ACA\ACF\ConditionalFormatting\FieldFormattableFactory;
use ACA\ACF\Editing;
use ACA\ACF\Export;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\ACF\Search;
use ACA\ACF\Setting\ComponentFactory\ExtraActions;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Sorting;
use ACA\ACF\Value;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;

class FieldFactory extends AcfFactory
{

    use ACP\Column\FormatterSettingsTrait;

    private Search\ComparisonFactory $comparison_factory;

    private Editing\ServiceFactory $service_factory;

    private Sorting\ModelFactory $sorting_factory;

    private Export\FormatterFactory $export_service_factory;

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date;

    private FieldFormattableFactory $field_formattable_factory;

    private BeforeAfter $before_after_factory;

    private Editing\StorageFactory $storage_factory;

    private ExtraActions $extra_actions;

    protected Value\ValueFormatterFactory $formatter_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        TableScreenContext $table_context,
        FieldComponentFactory $component_factory,
        Value\ValueFormatterFactory $formatter_factory,
        Search\ComparisonFactory $comparison_factory,
        Editing\ServiceFactory $service_factory,
        Sorting\ModelFactory $sorting_factory,
        Export\FormatterFactory $export_service_factory,
        FieldFormattableFactory $field_formattable_factory,
        Editing\StorageFactory $storage_factory,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date,
        ExtraActions $extra_actions
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $component_factory,
            $table_context,
        );

        $this->comparison_factory = $comparison_factory;
        $this->service_factory = $service_factory;
        $this->sorting_factory = $sorting_factory;
        $this->export_service_factory = $export_service_factory;
        $this->filtering_date = $filtering_date;
        $this->formatter_factory = $formatter_factory;
        $this->field_formattable_factory = $field_formattable_factory;
        $this->storage_factory = $storage_factory;
        $this->extra_actions = $extra_actions;
    }

    private function get_base_formatter(): Formatter
    {
        return new Value\Formatter\GetFieldRaw($this->table_context, $this->field->get_meta_key());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = $this->get_formatters_from_settings(
            $this->get_settings($config)
        );

        $this->formatter_factory->add_field_formatters(
            $formatters,
            $this->field,
            $config
        );

        return FormatterCollection::from_formatter($this->get_base_formatter())
                                  ->merge($formatters);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return $this->comparison_factory->create(
            $this->field,
            $this->field->get_meta_key(),
            $this->table_context
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return $this->service_factory->create(
            $this->field,
            $this->storage_factory->create($this->field, $this->table_context)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->sorting_factory->create(
            $this->field,
            $this->field->get_meta_key(),
            $this->table_context,
            $config
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->export_service_factory->create(
            $this->field,
            $this->get_base_formatter(),
            $this->get_formatters($config)
        );
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return $this->field_formattable_factory->create($this->field, $this->get_base_formatter());
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->extra_actions->create($config), 40);
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);

        if (in_array($this->field->get_type(), [FieldType::TYPE_DATE_PICKER, FieldType::TYPE_DATE_TIME_PICKER], true)) {
            $builder->set_search(null, $this->filtering_date);
        }

        return $builder;
    }

}