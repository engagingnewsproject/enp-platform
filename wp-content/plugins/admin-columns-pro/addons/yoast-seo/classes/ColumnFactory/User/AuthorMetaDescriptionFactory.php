<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP\Column\AdvancedColumnFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class AuthorMetaDescriptionFactory extends AdvancedColumnFactory
{

    private const META_KEY = 'wpseo_metadesc';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_author_meta_desc';
    }

    public function get_label(): string
    {
        return __('SEO Meta Description', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new AC\Formatter\User\Meta(self::META_KEY));
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\TextArea(),
            new Editing\Storage\User\Meta(self::META_KEY)
        );
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta(self::META_KEY);
    }

}