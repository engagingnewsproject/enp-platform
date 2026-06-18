<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Sorting\Type\DataType;

class ScoreFactory extends OriginalColumnFactory
{

    private const META_KEY = '_yoast_wpseo_linkdex';

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Meta(self::META_KEY));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY, new DataType(DataType::NUMERIC));
    }

}