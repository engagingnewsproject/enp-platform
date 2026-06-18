<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment\Original;

use AC\Formatter\Comment\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Search;

class Date extends OriginalColumnFactory
{

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_component_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_component_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);

        $this->filter_component_factory = $filter_component_factory;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);
        $builder->set_search(null, $this->filter_component_factory);

        return $builder;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Property('comment_date'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Date\Date();
    }

}