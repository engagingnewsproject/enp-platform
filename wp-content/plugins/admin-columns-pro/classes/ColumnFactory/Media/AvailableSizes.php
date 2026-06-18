<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC\ColumnFactory;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting;

class AvailableSizes extends ACP\Column\EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct(
        ColumnFactory\Media\AvailableSizesFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Media\AvailableSizes();
    }

}