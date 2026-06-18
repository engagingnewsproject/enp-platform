<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\EventSeries\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\EC\Value\Formatter\EventSeries\Events;
use ACP\Column\OriginalColumnFactory;

class EventsFactory extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Events());
    }

}