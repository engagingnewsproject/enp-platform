<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\SeoPress\Editing;
use ACA\SeoPress\Value\Formatter;
use ACP;

final class XPreview extends ACP\Column\AdvancedColumnFactory
{

    protected function get_group(): ?string
    {
        return 'seopress_social';
    }

    public function get_label(): string
    {
        return __('X Preview', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-sp_social_x_preview';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\XPreview());
    }

}