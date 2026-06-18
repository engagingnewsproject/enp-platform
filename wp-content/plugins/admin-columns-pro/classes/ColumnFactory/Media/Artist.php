<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC;
use AC\Setting\Config;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Artist extends EnhancedColumnFactory
{

    private const META_KEY = 'album';

    use ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Media\ArtistFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Media\MetaDataText(self::META_KEY);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Media\MetaData\Audio(self::META_KEY);
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Media\MetaData(self::META_KEY);
    }

}