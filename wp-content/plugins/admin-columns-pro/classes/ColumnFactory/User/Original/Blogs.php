<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;
use ACP\Formatter\MsUser\Sites;

class Blogs extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Sites());
    }

}