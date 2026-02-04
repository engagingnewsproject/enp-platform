<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Sorting;
use ACP\Value;

class Revisions extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-revisions';
    }

    public function get_label(): string
    {
        return __('Revisions', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Formatter\Post\RevisionCount(),
            new ACP\Formatter\Post\ExtendedRevisionLink(new ACP\Value\ExtendedValue\Post\Revisions()),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new ACP\Formatter\Post\RevisionCount());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Revisions();
    }

}