<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\MetaBox;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MetaBox\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;

class Fields extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'metabox_custom';
    }

    public function get_column_type(): string
    {
        return 'column-mb-fields';
    }

    public function get_label(): string
    {
        return __('Fields', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Value\Formatter\MetaBox\Fields(),
        ]);
    }

}