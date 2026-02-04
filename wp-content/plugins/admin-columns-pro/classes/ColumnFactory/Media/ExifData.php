<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC;
use AC\Setting\Config;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Sorting;

class ExifData extends EnhancedColumnFactory
{

    use ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Media\ExifDataFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Media\Exif($config->get('exif_data', ''));
    }

}