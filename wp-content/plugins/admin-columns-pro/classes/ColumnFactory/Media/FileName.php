<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Sorting;

class FileName extends EnhancedColumnFactory
{

    use ConditionalFormatTrait;

    public function __construct(
        AC\ColumnFactory\Media\FileNameFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Media\FileName());
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\MetaFormat(
            new Sorting\FormatValue\FileName(),
            '_wp_attached_file'
        );
    }

}