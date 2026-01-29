<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\User;

use AC\Formatter\User\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

final class TwitterUsername extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use GroupTrait;

    private const META_KEY = 'twitter';

    public function get_column_type(): string
    {
        return 'column-rankmath-twitter_username';
    }

    public function get_label(): string
    {
        return __('Twitter username', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->with_formatter(new Meta(self::META_KEY));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new ACP\Editing\Storage\User\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\User\Meta(self::META_KEY);
    }

}