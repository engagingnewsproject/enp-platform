<?php

declare(strict_types=1);

namespace ACA\JetEngine\ColumnFactory\Relation;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\JetEngine\Editing;
use ACA\JetEngine\Search;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use Jet_Engine\Relations\Relation as JetEngineRelation;

class PostRelationFactory extends RelationFactory
{

    private ComponentFactory\LinkablePostProperty $post_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        JetEngineRelation $relation,
        bool $is_parent,
        ComponentFactory\LinkablePostProperty $post_property
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $relation,
            $is_parent
        );
        $this->post_property = $post_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = parent::get_settings($config);
        $settings->add($this->post_property->create($config));

        return $settings;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Relation\Post(
            $this->relation,
            $this->is_parent,
            $this->get_related_object()
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $storage = $this->is_parent
            ? new Editing\Storage\RelationshipChildren($this->relation)
            : new Editing\Storage\RelationshipParents($this->relation);

        return new Editing\Service\Relation\Post($storage, $this->has_many(), $this->get_related_object());
    }

}