<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\Formatter\User\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Search;

class Username extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Property('user_login'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\UserName();
    }

}