<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Formatter\Post\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;

class Modified extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Property('post_modified'));
    }
}