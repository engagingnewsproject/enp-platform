<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Sorting;
use ACP\Value;

class Images extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-images';
    }

    public function get_label(): string
    {
        return __('Images', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\PostContent(),
            new AC\Formatter\ImageUrlsFromContent($this->get_context($config)),
            new ACP\Formatter\Post\ImagesExtendedLink(
                new Value\ExtendedValue\Post\PostImages()
            ),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\ImageFileSizes();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\PostContent(),
            new AC\Formatter\ImageUrlsFromContent($this->get_context($config)),
            new AC\Formatter\ImageSize(),
            new AC\Formatter\TotalSum(),
            new AC\Formatter\FileSizeReadable(),
        ]);
    }

}