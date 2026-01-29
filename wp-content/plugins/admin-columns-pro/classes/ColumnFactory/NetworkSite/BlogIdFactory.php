<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Formatter\Id;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\AdvancedColumnFactory;

class BlogIdFactory extends AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-blog_id';
    }

    public function get_label(): string
    {
        return __('Blog ID', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Id(),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}