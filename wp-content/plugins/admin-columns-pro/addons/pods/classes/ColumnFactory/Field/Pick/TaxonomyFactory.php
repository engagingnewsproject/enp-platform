<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\Pods\ColumnFactory\Field\MetaQueryTrait;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Search\Comparison\PickTaxonomy;
use ACA\Pods\Value\Formatter\IdCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting\FormatValue\SettingFormatter;
use ACP\Sorting\Model\MetaFormatFactory;

class TaxonomyFactory extends FieldFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use MetaQueryTrait;

    private ComponentFactory\TermProperty $term_property;

    private ComponentFactory\TermLink $term_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        ComponentFactory\TermProperty $term_property,
        ComponentFactory\TermLink $term_link,
        TableScreenContext $table_context
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $table_context
        );
        $this->term_property = $term_property;
        $this->term_link = $term_link;
        $this->post_type = $field->get_post_type();
    }

    private function get_related_taxonomy(): ?string
    {
        return $this->field->get_field()->get_arg('pick_val', '');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->term_property->create($config))
                     ->add($this->term_link->create($config));
    }

    protected function is_multiple(): bool
    {
        return 'multi' === $this->field->get_field()->get_arg('pick_format_type');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\PickTaxonomy(
            new Editing\Storage\Field(
                $this->field,
                new Editing\Storage\Read\DbRaw($this->field->get_name(), $this->field->get_meta_type())
            ),
            $this->is_multiple(),
            $this->get_related_taxonomy()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        if ($this->is_multiple()) {
            return null;
        }

        $setting_formatters = new SettingFormatter($this->term_property->create($config)->get_formatters());

        return (new MetaFormatFactory())->create(
            $this->field->get_meta_type(),
            $this->field->get_name(),
            $setting_formatters,
            null,
            [
                'taxonomy' => (string)$this->field->get_taxonomy(),
                'post_type' => (string)$this->post_type,
            ]
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new PickTaxonomy($this->field->get_name(), (array)$this->get_related_taxonomy());
    }

    protected function get_base_formatters(Config $config): FormatterCollection
    {
        return parent::get_base_formatters($config)->add(new IdCollection('term_id'));
    }

}