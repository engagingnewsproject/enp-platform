<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post\Original;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\OriginalColumnFactory;

class FocusKeywordFactory extends OriginalColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private const META_KEY = '_yoast_wpseo_focuskw';

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_placeholder(
                __('Enter your SEO Focus Keywords', 'codepress-admin-columns')
            ),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Meta(self::META_KEY));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

}