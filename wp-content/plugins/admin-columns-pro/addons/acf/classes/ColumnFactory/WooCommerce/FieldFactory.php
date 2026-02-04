<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\WooCommerce;

use AC\Formatter;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactory;
use ACA\ACF\ConditionalFormatting;
use ACA\ACF\Editing;
use ACA\ACF\Export\FormatterFactory;
use ACA\ACF\Field;
use ACA\ACF\Search;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Sorting;
use ACA\ACF\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class FieldFactory extends ColumnFactory\AcfFactory
{

    use ACP\Column\FormatterSettingsTrait;

    protected Value\ValueFormatterFactory $formatter_factory;

    private Editing\ServiceFactory $editing_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        FieldComponentFactory $component_factory,
        Value\ValueFormatterFactory $formatter_factory,
        Editing\ServiceFactory $editing_factory,
        TableScreenContext $table_context
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $component_factory,
            $table_context
        );

        $this->formatter_factory = $formatter_factory;
        $this->editing_factory = $editing_factory;
    }

    private function get_base_formatter(): Formatter
    {
        return new Value\Formatter\GetFieldRaw(
            $this->table_context,
            $this->field->get_meta_key()
        );
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

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return $this->editing_factory->create(
            $this->field,
            (new Editing\StorageFactory())->create($this->field, $this->table_context)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new Search\ComparisonFactory\WcOrderMetaFactory())->create($this->field);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new Sorting\WcOrderModelFactory())->create($this->field);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return (new FormatterFactory())->create(
            $this->field,
            $this->get_base_formatter(),
            $this->get_formatters($config)
        );
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return (new ConditionalFormatting\FieldFormattableFactory())->create($this->field, $this->get_base_formatter());
    }

}