<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class SocialTitleFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_yoast_wpseo_opengraph-title';

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_facebook_title';
    }

    public function get_label(): string
    {
        return __('Social Title', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new AC\Formatter\Post\Meta('_yoast_wpseo_opengraph-title'));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new ACP\Editing\Storage\Meta(self::META_KEY, new AC\MetaType(AC\MetaType::POST))
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }
}