<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Formatter\Composite;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter\Product\StockQuantity;
use ACA\WC\Value\Formatter\Product\StockStatus;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Sorting\Type\DataType;

class StockFactory extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\Stock();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new Composite([
                new StockStatus(),
                new StockQuantity(false),
            ],
                ' / '
            )
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Stock();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_stock', new DataType(DataType::NUMERIC));
    }

}