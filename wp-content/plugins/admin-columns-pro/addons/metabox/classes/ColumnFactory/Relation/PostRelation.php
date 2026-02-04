<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\Relation;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\MetaBox\Editing;
use ACA\MetaBox\Entity;
use ACA\MetaBox\Search;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;

class PostRelation extends Relation
{

    use ConditionalFormat\ConditionalFormatTrait;

    private ComponentFactory\LinkablePostProperty $post_property;

    private ComponentFactory\PostLink $post_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        Entity\Relation $relation,
        ComponentFactory\LinkablePostProperty $post_property,
        ComponentFactory\PostLink $post_link
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $column_type, $relation);
        $this->post_property = $post_property;
        $this->post_link = $post_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->post_property->create($config))
                     ->add($this->post_link->create($config));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Relation\Post($this->relation);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Relation\Post($this->relation);
    }

}