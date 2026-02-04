<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Formatter\NetworkSite\Status;

class StatusFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-msite_status';
    }

    public function get_label(): string
    {
        return __('Status', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Status(),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}