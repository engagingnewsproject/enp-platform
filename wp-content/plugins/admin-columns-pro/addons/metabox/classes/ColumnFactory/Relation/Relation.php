<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\Relation;

use AC\Column\Context;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\MetaBox;
use ACA\MetaBox\Entity;
use ACA\MetaBox\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;

class Relation extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected Entity\Relation $relation;

    private string $column_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        Entity\Relation $relation
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->relation = $relation;
    }

    protected function get_group(): ?string
    {
        return 'metabox_relation';
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    public function get_label(): string
    {
        return $this->relation->get_title();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Value\Formatter\Relation\RelatedIds($this->relation));
    }

    protected function get_context(Config $config): Context
    {
        return new MetaBox\Column\RelationContext(
            $config,
            $this->get_label(),
            $this->relation
        );
    }
}