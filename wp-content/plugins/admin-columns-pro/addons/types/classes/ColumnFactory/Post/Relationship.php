<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Post;

use AC\Column\Context;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\LinkablePostProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA;
use ACA\Types\Editing;
use ACA\Types\Search;
use ACA\Types\Value\Formatter\TypesRelatedPost;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use Toolset_Relationship_Definition;

abstract class Relationship extends ACP\Column\AdvancedColumnFactory
{

    protected string $column_type;

    protected string $label;

    protected Toolset_Relationship_Definition $relationship;

    private LinkablePostProperty $post_property;

    abstract protected function get_related_post_type(): string;

    abstract protected function get_relation_type(): string;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Toolset_Relationship_Definition $relationship,
        LinkablePostProperty $post_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->relationship = $relationship;
        $this->post_property = $post_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->post_property->create($config));
    }

    protected function get_group(): string
    {
        return 'types_relationship';
    }

    public function get_label(): string
    {
        return $this->label;
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new TypesRelatedPost($this->relationship->get_slug(), $this->get_relation_type()));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Relationship(
            new Editing\Storage\Relationship\ParentRelation($this->relationship),
            $this->get_related_post_type()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Post\Relationship(
            $this->relationship->get_slug(),
            $this->get_related_post_type(),
            'child',
            'parent'
        );
    }

    protected function get_context(Config $config): Context
    {
        return new ACA\Types\Column\RelationshipContext(
            $config,
            $this->get_label(),
            $this->relationship->get_definition_array()
        );
    }

}