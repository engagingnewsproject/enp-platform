<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Search;
use ACP\Sorting;

class ReadingTime extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_yoast_wpseo_estimated-reading-time-minutes';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_reading_time';
    }

    public function get_label(): string
    {
        return __('Reading time', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\Meta(AC\MetaType::create_post_meta(), self::META_KEY))
                     ->add(
                         new AC\Formatter\CountLabelFormatter(
                             __('%d minute', 'codepress-admin-columns'),
                             __('%d minutes', 'codepress-admin-columns')
                         )
                     );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Number(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY, Sorting\Type\DataType::create_numeric());
    }
}