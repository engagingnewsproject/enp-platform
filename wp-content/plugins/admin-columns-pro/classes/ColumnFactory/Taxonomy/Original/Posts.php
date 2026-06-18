<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Taxonomy\Original;

use AC\Formatter\Term\TermProperty;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;

class Posts extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new TermProperty('count'));
    }

}