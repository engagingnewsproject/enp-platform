<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\YoastSeo\Value\Formatter\KeywordOccurence;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class FocusKeywordCountFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = '_yoast_wpseo_focuskw';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-wpseo_column_focuskw_count';
    }

    public function get_label(): string
    {
        return __('Keyphrase Occurrence', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new KeywordOccurence());
    }

}