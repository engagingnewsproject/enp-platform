<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC\Formatter\Post\PostTermsOriginal;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Tags extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new PostTermsOriginal('post_tag'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Taxonomy('post_tag');
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Taxonomy('post_tag');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\Taxonomy(
            'post_tag',
            true
        );
    }

}