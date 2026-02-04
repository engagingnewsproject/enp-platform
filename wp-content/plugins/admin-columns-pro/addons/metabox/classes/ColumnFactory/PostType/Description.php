<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactory\PostType;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\MetaBox\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;

class Description extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'metabox_custom';
    }

    public function get_column_type(): string
    {
        return 'column-mb-pt_description';
    }

    public function get_label(): string
    {
        return __('Description', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Value\Formatter\PostType\PostTypeSetting('description'));
    }

}