<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\BP;
use ACA\BP\Value\Formatter\Group\GroupProperty;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;

class Name extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new BP\Editing\Service\Group\NameOnly();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new GroupProperty('name'));
    }

}