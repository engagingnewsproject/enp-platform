<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment\Original;

use AC\Formatter\Comment\Property;
use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Search;
use ACP\Sorting;

class Response extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new Property('comment_post_ID'),
            new PostTitle(),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Comment\Response();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Post();
    }

}