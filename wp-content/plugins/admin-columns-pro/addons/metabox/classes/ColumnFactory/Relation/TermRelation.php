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

class TermRelation extends Relation
{

    use ConditionalFormat\ConditionalFormatTrait;

    private ComponentFactory\TermProperty $term_property;

    private ComponentFactory\TermLink $term_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        Entity\Relation $relation,
        ComponentFactory\TermProperty $term_property,
        ComponentFactory\TermLink $term_link

    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $column_type, $relation);
        $this->term_property = $term_property;
        $this->term_link = $term_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->term_property->create($config))
                     ->add($this->term_link->create($config));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Relation\Term($this->relation);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Relation\Term($this->relation);
    }

}