<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\Relation;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\UserLink;
use AC\Setting\ComponentFactory\UserProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\MetaBox\Editing;
use ACA\MetaBox\Entity;
use ACA\MetaBox\Search;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;

class UserRelation extends Relation
{

    use ConditionalFormat\ConditionalFormatTrait;

    private UserProperty $user_property;

    private UserLink $user_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        Entity\Relation $relation,
        UserProperty $user_property,
        UserLink $user_link
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $column_type, $relation);

        $this->user_property = $user_property;
        $this->user_link = $user_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->user_property->create($config))
                     ->add($this->user_link->create($config));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Relation\User($this->relation);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Relation\User($this->relation);
    }

}