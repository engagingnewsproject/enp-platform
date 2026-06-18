<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Formatter\NetworkSite\SiteProperty;

class PathFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-msite_path';
    }

    public function get_label(): string
    {
        return __('Path', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new SiteProperty('path'),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}