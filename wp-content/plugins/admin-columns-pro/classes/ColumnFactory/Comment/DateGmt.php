<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\Formatter\Comment\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class DateGmt extends ACP\Column\EnhancedColumnFactory
{

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_component_factory;

    public function __construct(
        AC\ColumnFactory\Comment\DateGmtFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filter_component_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
        $this->filter_component_factory = $filter_component_factory;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_search(null, $this->filter_component_factory);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\OrderBy('comment_date_gmt');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Date\Gmt();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Property('comment_date_gmt'));
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new AC\FormatterCollection([
                    new Property('comment_date_gmt'),
                ]),
                'Y-m-d H:i:s',
            )
        );
    }

}