<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Value\Formatter;
use ACA\WC\Export;
use ACP;
use ACP\Column\OriginalColumnFactory;

class OrderNumber extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Order\OrderNumber());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\ID();
    }

}