<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory\Post;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\LinkablePostProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACA\Types\Editing;
use ACA\Types\Search;
use ACA\Types\Value\Formatter\TypesIntermediaryRelatedPost;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class IntermediaryRelationship extends ACP\Column\AdvancedColumnFactory
{

    protected string $column_type;

    protected string $label;

    private LinkablePostProperty $post_property;

    private PostTypeSlug $current_post_type;

    private PostTypeSlug $related_post_type;

    private string $relation_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        PostTypeSlug $current_post_type,
        PostTypeSlug $related_post_type,
        string $relation_type,
        LinkablePostProperty $post_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->current_post_type = $current_post_type;
        $this->related_post_type = $related_post_type;
        $this->relation_type = $relation_type;
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
                     ->prepend(new TypesIntermediaryRelatedPost($this->current_post_type, $this->relation_type));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Post\IntermediaryRelationship(
            (string)$this->current_post_type,
            (string)$this->related_post_type,
            $this->relation_type
        );
    }

}