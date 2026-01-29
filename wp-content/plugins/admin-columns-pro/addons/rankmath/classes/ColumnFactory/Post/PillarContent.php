<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post;

use AC\Formatter\Post\Meta;
use AC\Formatter\YesIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

final class PillarContent extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use GroupTrait;

    private const META_KEY = 'rank_math_pillar_content';

    public function get_column_type(): string
    {
        return 'column-rankmath-pillar_content';
    }

    public function get_label(): string
    {
        return __('Pillar Content', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Meta(self::META_KEY))
                     ->add(new YesIcon());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Text(),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta(self::META_KEY));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Checkmark(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

}