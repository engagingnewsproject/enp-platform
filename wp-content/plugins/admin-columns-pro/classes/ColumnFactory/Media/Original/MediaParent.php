<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media\Original;

use AC\Formatter\Post\PostTitle;
use AC\Formatter\Post\Property;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Search;

class MediaParent extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new Property('post_parent'),
            new PostTitle(),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\PostParent('attachment');
    }

}