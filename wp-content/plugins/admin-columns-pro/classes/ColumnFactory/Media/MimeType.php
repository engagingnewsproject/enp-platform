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

class MimeType extends EnhancedColumnFactory
{

    use ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Media\MimeTypeFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Media\MimeType();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Media\MimeType();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Media\MimeType();
    }

}