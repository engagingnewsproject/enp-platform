<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post;

use AC\Formatter\Linkable;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

final class CanonicalUrl extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use GroupTrait;

    private const META_KEY = 'rank_math_canonical_url';

    public function get_column_type(): string
    {
        return 'column-rankmath-canonical_url';
    }

    public function get_label(): string
    {
        return __('Canonical URL', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Meta(self::META_KEY))
                     ->add(new Linkable());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Url())->set_clear_button(true),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

}