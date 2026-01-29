<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\Meta;

use AC\Formatter\Aggregate;
use AC\Formatter\Collection\Separator;
use AC\Formatter\PregReplace;
use AC\Formatter\StripTags;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactory\AcfFactory;
use ACA\ACF\ConditionalFormatting\FieldFormattableFactory;
use ACA\ACF\Field;
use ACA\ACF\Search;
use ACA\ACF\Setting\ComponentFactory;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class RepeaterFieldFactory extends AcfFactory
{

    use ACP\Column\FormatterSettingsTrait;

    private FieldFormattableFactory $field_formattable_factory;

    protected Value\ValueFormatterFactory $formatter_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field\Type\Repeater $field,
        TableScreenContext $table_context,
        FieldComponentFactory $component_factory,
        Value\ValueFormatterFactory $formatter_factory,
        FieldFormattableFactory $field_formattable_factory
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

        $this->field = $field;
        $this->formatter_factory = $formatter_factory;
        $this->field_formattable_factory = $field_formattable_factory;
    }

    public function get_sub_field(Config $config): ?Field
    {
        return $this->field->get_sub_field($config->get(ComponentFactory\RepeaterSubField::NAME, ''));
    }

    private function get_base_formatter(Field $sub_field): Value\Formatter\RawRepeater
    {
        return new Value\Formatter\RawRepeater(
            $this->table_context,
            $this->field->get_meta_key(),
            $sub_field->get_hash()
        );
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = new ComponentCollection([
            (new ComponentFactory\RepeaterDisplay(
                new ComponentFactory\RepeaterSubField($this->field)
            ))->create($config),
        ]);

        $display = $config->get(ComponentFactory\RepeaterDisplay::NAME, '');
        $sub_field = $this->get_sub_field($config);

        if ($display === 'subfield' && $sub_field) {
            foreach ($this->component_factory->create($sub_field) as $component) {
                $settings->add($component->create($config));
            }
        }

        return $settings;
    }

    protected function create_formatter(Config $config, ?string $separator = null): FormatterCollection
    {
        $separator = $separator ?? '<div class="ac-repeater-divider"></div>';
        $sub_field = $this->get_sub_field($config);

        if ($sub_field) {
            $formatters = FormatterCollection::from_formatter(
                $this->get_base_formatter($sub_field)
            );

            $aggregate = $this->formatter_factory->get_field_formatters(
                $this->get_formatters_from_settings($this->get_settings($config)),
                $sub_field,
                $config
            );

            $formatters->add(new Aggregate($aggregate));
            $formatters->add(new Separator($separator));

            return $formatters;
        }

        return FormatterCollection::from_formatter(
            new Value\Formatter\RepeaterCount($this->table_context, $this->field->get_meta_key()),
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return $this->create_formatter($config);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        $delimiter = (string)apply_filters('ac/acf/export/repeater/delimiter', ';');

        return $this->create_formatter($config, $delimiter)
                    ->with_formatter(new StripTags())
                    ->with_formatter((new PregReplace())->replace_multiple_spaces(' '));
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        $sub_field = $this->get_sub_field($config);

        if ( ! $sub_field) {
            return null;
        }

        return $this->field_formattable_factory->create($sub_field, $this->get_base_formatter($sub_field));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $sub_field = $this->get_sub_field($config);

        return $sub_field !== null
            ? (new Search\ComparisonFactory\Repeater())->create(
                $sub_field,
                $this->field->get_meta_key(),
                (string)$this->table_context->get_meta_type()
            )
            : null;
    }

}