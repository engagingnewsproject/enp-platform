<?php

declare(strict_types=1);

namespace ACA\JetEngine\ColumnFactory\Relation;

use AC\Column\Context;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\JetEngine\Column\RelationContext;
use ACA\JetEngine\Service\ColumnGroups;
use ACA\JetEngine\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use Jet_Engine\Relations\Relation as JetEngineRelation;

class RelationFactory extends AdvancedColumnFactory
{

    private string $column_type;

    private string $label;

    protected JetEngineRelation $relation;

    protected bool $is_parent;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        JetEngineRelation $relation,
        bool $is_parent
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->relation = $relation;
        $this->is_parent = $is_parent;
    }

    protected function get_group(): ?string
    {
        return ColumnGroups::JET_ENGINE_RELATION;
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $base_formatter = $this->is_parent
            ? new Value\Formatter\Relation\ChildIds($this->relation)
            : new Value\Formatter\Relation\ParentIds($this->relation);

        $formatters->prepend($base_formatter);

        return $formatters;
    }

    protected function get_related_object(): string
    {
        return $this->is_parent
            ? (string)explode('::', $this->relation->get_args('child_object'))[1]
            : (string)explode('::', $this->relation->get_args('parent_object'))[1];
    }

    protected function has_many(): bool
    {
        switch ($this->relation->get_args('type')) {
            case 'one_to_many':
                return $this->is_parent;
            case 'many_to_many':
                return true;
            default:
                return false;
        }
    }

    protected function get_context(Config $config): Context
    {
        return new RelationContext($config, $this->get_label(), $this->relation);
    }

}