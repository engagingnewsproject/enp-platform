<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Caption extends EnhancedColumnFactory
{

    use ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Media\CaptionFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Excerpt());
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Media\Caption();
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Post\Excerpt();
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\PostField('post_excerpt');
    }

}