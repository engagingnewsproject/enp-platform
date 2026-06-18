<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event\Original;

use AC;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\OriginalColumnFactory;
use ACP\Filtering\Setting\ComponentFactory\FilteringDate;

class StartDateFactory extends OriginalColumnFactory
{

    private const META_KEY = '_EventStartDate';

    private FilteringDate $filter_date_format;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        FilteringDate $filter_date_format
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->filter_date_format = $filter_date_format;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_search(null, $this->filter_date_format);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\DateTime(),
            new EC\Editing\Storage\Event\StartDate()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\DateTime\ISO(
            self::META_KEY,
            (new AC\Meta\QueryMetaFactory())->create_with_post_type(self::META_KEY, 'tribe_events')
        );
    }

}