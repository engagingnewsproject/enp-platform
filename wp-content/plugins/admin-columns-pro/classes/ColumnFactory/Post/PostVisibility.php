<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Search;

class PostVisibility extends ACP\Column\AdvancedColumnFactory
{

    public function get_label(): string
    {
        return __('Post Visibility', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-post_visibility';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Post\PostVisibility(),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\PostVisibility();
    }

}