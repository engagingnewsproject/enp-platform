<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Search;

class PostType extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-post_type';
    }

    public function get_label(): string
    {
        return __('Post Type', 'codepress-admin-columns');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\PostType();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Comment\PostType(),
            new ACP\Formatter\PostType\SingularLabel(),
        ]);
    }

}