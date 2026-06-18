<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\Meta;

use AC\Formatter;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactory\AcfFactory;
use ACA\ACF\ConditionalFormatting\FieldFormattableFactory;
use ACA\ACF\Editing;
use ACA\ACF\Export;
use ACA\ACF\Field;
use ACA\ACF\Search;
use ACA\ACF\Service\ColumnGroup;
use ACA\ACF\Setting\ComponentFactory\ExtraActions;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Sorting;
use ACA\ACF\Storage\GroupFieldStorage;
use ACA\ACF\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;

class GroupFieldFactory extends AcfFactory
{

    use ACP\Column\FormatterSettingsTrait;

    protected Field\Type\GroupSubField $group_sub_field;

    private Search\ComparisonFactory $search_comparison_factory;

    private Editing\ServiceFactory $editing_service_factory;

    private Export\FormatterFactory $export_service_factory;

    private Sorting\ModelFactory $sorting_model_factory;

    private FieldFormattableFactory $field_formattable_factory;

    protected Value\ValueFormatterFactory $formatter_factory;

    protected FieldComponentFactory $component_factory;

    private ExtraActions $extra_actions;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field\Type\GroupSubField $field,
        TableScreenContext $table_context,
        FieldComponentFactory $component_factory,
        Value\ValueFormatterFactory $formatter_factory,
        Search\ComparisonFactory $search_comparison_factory,
        Export\FormatterFactory $export_service_factory,
        Editing\ServiceFactory $editing_service_factory,
        Sorting\ModelFactory $sorting_model_factory,
        FieldFormattableFactory $field_formattable_factory,
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

        $this->group_sub_field = $field;
        $this->component_factory = $component_factory;
        $this->search_comparison_factory = $search_comparison_factory;
        $this->editing_service_factory = $editing_service_factory;
        $this->export_service_factory = $export_service_factory;
        $this->formatter_factory = $formatter_factory;
        $this->sorting_model_factory = $sorting_model_factory;
        $this->field_formattable_factory = $field_formattable_factory;
        $this->extra_actions = $extra_actions;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->extra_actions->create($config), 40);
    }

    protected function get_group(): string
    {
        $parent = $this->field->get_settings()['_ac_group']['parent'] ?? '';

        if ($parent && ! is_numeric($parent)) {
            $field_group = acf_get_field_group($parent);

            if ($field_group) {
                $parent = $field_group['ID'];
            }
        }

        return $parent
            ? ColumnGroup::SLUG . $parent
            : ColumnGroup::SLUG;
    }

    private function get_base_formatter(): Formatter
    {
        return new Value\Formatter\GroupField(
            $this->table_context,
            $this->group_sub_field->get_group_field()->get_meta_key(),
            $this->group_sub_field->get_sub_field()->get_hash()
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = $this->get_formatters_from_settings(
            $this->get_settings($config)
        );

        $this->formatter_factory->add_field_formatters(
            $formatters,
            $this->group_sub_field->get_sub_field(),
            $config
        );

        return FormatterCollection::from_formatter($this->get_base_formatter())
                                  ->merge($formatters);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return $this->sorting_model_factory->create(
            $this->field,
            $this->group_sub_field->get_meta_key(),
            $this->table_context,
            $config
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return $this->search_comparison_factory->create(
            $this->group_sub_field->get_sub_field(),
            $this->group_sub_field->get_meta_key(),
            $this->table_context
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

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $storage = new Editing\Storage\GroupField(
            $this->group_sub_field->get_group_field()->get_meta_key(),
            $this->group_sub_field->get_sub_field()->get_hash(),
            $this->field->get_meta_key(),
            new GroupFieldStorage($this->table_context)
        );

        return $this->editing_service_factory->create(
            $this->group_sub_field->get_sub_field(),
            $storage
        );
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return $this->field_formattable_factory->create(
            $this->group_sub_field->get_sub_field(),
            $this->get_base_formatter()
        );
    }

}