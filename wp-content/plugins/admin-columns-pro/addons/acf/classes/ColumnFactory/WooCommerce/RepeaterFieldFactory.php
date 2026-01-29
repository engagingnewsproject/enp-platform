<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\WooCommerce;

use AC\Formatter;
use AC\Formatter\Aggregate;
use AC\Formatter\Collection\Separator;
use AC\Formatter\StripTags;
use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactory;
use ACA\ACF\ConditionalFormatting;
use ACA\ACF\Field;
use ACA\ACF\Setting\ComponentFactory;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class RepeaterFieldFactory extends ColumnFactory\AcfFactory
{

    use ACP\Column\FormatterSettingsTrait;

    protected Value\ValueFormatterFactory $formatter_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        FieldComponentFactory $component_factory,
        Value\ValueFormatterFactory $formatter_factory
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $component_factory,
            new TableScreenContext(
                new MetaType(MetaType::POST),
                new PostTypeSlug('shop_order')
            )
        );

        $this->formatter_factory = $formatter_factory;
    }

    public function get_sub_field(Config $config): ?Field
    {
        return $this->field->get_sub_field($config->get(ComponentFactory\RepeaterSubField::NAME, ''));
    }

    private function get_base_formatter(Field $sub_field): Formatter
    {
        return new Value\Formatter\RawRepeater(
            $this->table_context,
            $this->field->get_meta_key(),
            $sub_field->get_hash()
        );
    }

    protected function create_formatter(Config $config, ?string $separator = null): FormatterCollection
    {
        $separator = $separator ?? '<div class="ac-repeater-divider"></div>';
        $sub_field = $this->get_sub_field($config);
        $formatters = new FormatterCollection([]);

        if ($sub_field) {
            $formatters->add($this->get_base_formatter($sub_field));

            $aggregate = $this->formatter_factory->get_field_formatters(
                $this->get_formatters_from_settings($this->get_settings($config)),
                $sub_field,
                $config
            );

            $formatters->add(new Aggregate($aggregate));
            $formatters->add(new Separator($separator));
        }

        return $formatters;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return $this->create_formatter($config);
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $subfield_factory = new ComponentFactory\RepeaterSubField($this->field);
        $sub_field = $this->get_sub_field($config);

        $settings = parent::get_settings($config)
                          ->add($subfield_factory->create($config));
        if ($sub_field) {
            foreach ($this->component_factory->create($sub_field) as $component) {
                $settings->add($component->create($config));
            }
        }

        return $settings;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        $delimiter = (string)apply_filters('ac/acf/export/repeater/delimiter', ';');

        return $this->create_formatter($config, $delimiter)
                    ->with_formatter(new StripTags());
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        $sub_field = $this->get_sub_field($config);

        if ( ! $sub_field) {
            return null;
        }

        return (new ConditionalFormatting\FieldFormattableFactory())->create(
            $this->field,
            $this->get_base_formatter($sub_field)
        );
    }

}