<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\OriginalColumnFactory;

class Registered extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\User\Property('user_registered'));
    }

}