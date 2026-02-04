<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group\Original;

use AC\Setting\Config;
use ACA\BP;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;

class Status extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new BP\Editing\Service\Group\Status();
    }

}