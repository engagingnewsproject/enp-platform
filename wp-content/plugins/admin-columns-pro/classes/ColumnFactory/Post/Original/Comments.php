<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Search;
use ACP\Search\Comparison\Post\CommentCount;

class Comments extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\CommentCount('total_comments'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\CommentCount([
            CommentCount::STATUS_APPROVED,
        ]);
    }

}