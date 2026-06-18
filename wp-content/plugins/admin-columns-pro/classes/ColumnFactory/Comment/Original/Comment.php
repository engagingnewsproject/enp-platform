<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment\Original;

use AC\Formatter\Comment\CommentText;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Comment extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Comment\Content();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new CommentText());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\OrderBy('comment_content');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\Content();
    }

}