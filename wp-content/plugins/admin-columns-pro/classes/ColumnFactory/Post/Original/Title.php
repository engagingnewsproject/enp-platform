<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Search;

class Title extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new PostTitle());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Title();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            (new Editing\View\Text())->set_placeholder(__('Add title', 'codepress-admin-columns')),
            new Editing\Storage\Post\Field('post_title')
        );
    }

}