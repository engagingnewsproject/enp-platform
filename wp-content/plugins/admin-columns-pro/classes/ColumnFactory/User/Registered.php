<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Registered extends EnhancedColumnFactory
{

    private ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date_factory;

    public function __construct(
        AC\ColumnFactory\User\RegisteredDateFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        ACP\Filtering\Setting\ComponentFactory\FilteringDate $filtering_date_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->filtering_date_factory = $filtering_date_factory;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)->set_search(null, $this->filtering_date_factory);
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new AC\FormatterCollection([
                    new AC\Formatter\User\Property('user_registered'),
                ]),
                'Y-m-d H:i:s',
            )
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Registered();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\OrderBy('registered');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Date\Registered();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\User\Property('user_registered'));
    }

}