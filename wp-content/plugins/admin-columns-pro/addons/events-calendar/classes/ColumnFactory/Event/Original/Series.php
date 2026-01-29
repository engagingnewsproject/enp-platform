<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC;
use ACA\EC\Value\Formatter\EventSeries\Title;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Series extends OriginalColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new EC\Search\Event\HasSeries();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        // TODO test
        return FormatterCollection::from_formatter(new Title());
    }

}