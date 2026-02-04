<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Formatter\Comment\ReplyCount;
use ACP\Search;

class HasReplies extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-has_replies';
    }

    public function get_label(): string
    {
        return __('Has Replies', 'codepress-admin-columns');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\HasReplies();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ReplyCount(),
            new AC\Formatter\YesNoIcon(),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new ReplyCount());
    }

}