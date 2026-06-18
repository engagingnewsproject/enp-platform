<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Editing;
use ACP\Formatter\NetworkSite\SiteOption;

class BlogNameFactory extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-msite_name';
    }

    public function get_label(): string
    {
        return __('Name', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new SiteOption('blogname'),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Text(),
            new Editing\Storage\Site\Option('blogname')
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}