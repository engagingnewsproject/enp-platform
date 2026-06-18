<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC\Formatter\Post\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Filtering\Setting\ComponentFactory\FilteringDate;
use ACP\Search;

class Date extends OriginalColumnFactory
{

    private PostTypeSlug $post_type;

    private FilteringDate $date_filter;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        PostTypeSlug $post_type,
        FilteringDate $date_filter
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->post_type = $post_type;
        $this->date_filter = $date_filter;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_search(null, $this->date_filter);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Date();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Property('post_date'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Date\PostDate((string)$this->post_type);
    }

}