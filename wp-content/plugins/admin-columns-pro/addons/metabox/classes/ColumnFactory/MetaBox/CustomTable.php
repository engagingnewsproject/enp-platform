<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\MetaBox;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MetaBox\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;

class CustomTable extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'metabox_custom';
    }

    public function get_column_type(): string
    {
        return 'column-mb-custom_table';
    }

    public function get_label(): string
    {
        return __('Custom Table', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta('settings'),
            new Value\Formatter\MetaBox\CustomTable(),
        ]);
    }

}