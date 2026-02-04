<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class PingStatus extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Post\PingStatusFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Property('ping_status'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\PingStatus();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\PingStatus();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\PostField('ping_status');
    }

}