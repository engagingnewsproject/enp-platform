<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MLA;
use ACP\Column\OriginalColumnFactory;

class AttachedTo extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new MLA\Export\Formatter\AttachedTo());
    }

}