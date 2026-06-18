<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;

class TitleName extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\PostTitle());
    }
}