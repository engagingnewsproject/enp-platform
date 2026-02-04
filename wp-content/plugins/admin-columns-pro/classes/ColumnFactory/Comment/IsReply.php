<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Search;

class IsReply extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-is_reply';
    }

    public function get_label(): string
    {
        return __('Is Reply', 'codepress-admin-columns');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\IsReply();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Comment\CommentParent(),
            new AC\Formatter\YesNoIcon(),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new ACP\Formatter\Comment\IsCommentParent());
    }

}