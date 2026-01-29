<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Formatter\Post\PostParentId;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;

class PostParent extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new PostParentId());
    }
}