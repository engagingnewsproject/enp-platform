<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Author extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\Author();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Author(),
            new AC\Formatter\User\Property('display_name'),
        ]);
    }

}