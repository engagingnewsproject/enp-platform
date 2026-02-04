<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\OriginalColumnFactory;

class NameFactory extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Text(),
            new ACP\Editing\Storage\Post\Field('post_title')
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new PostTitle());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Title();
    }

}