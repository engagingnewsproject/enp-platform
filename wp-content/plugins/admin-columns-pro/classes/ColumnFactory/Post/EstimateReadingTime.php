<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\Formatter\Post\PostContent;
use AC\Formatter\ReadingTime;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting;

class EstimateReadingTime extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Post\EstimateReadingTimeFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\EstimateReadingTime();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new PostContent(),
            new ReadingTime((int)$config->get('words_per_minute', 200)),
        ]);
    }

}