<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\YoastSeo\Value\Formatter\User\FallbackMetaTitle;
use ACP\Column\AdvancedColumnFactory;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class AuthorMetaTitleFactory extends AdvancedColumnFactory
{

    private const META_KEY = 'wpseo_title';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_author_title';
    }

    public function get_label(): string
    {
        return __('SEO Title', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\User\Meta(self::META_KEY))
                     ->add(new FallbackMetaTitle());
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Text(),
            new Editing\Storage\User\Meta(self::META_KEY)
        );
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta(self::META_KEY);
    }

}