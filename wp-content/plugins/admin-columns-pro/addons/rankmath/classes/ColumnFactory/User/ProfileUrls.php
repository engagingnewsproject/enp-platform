<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACA\RankMath\Editing;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Search\Operators;

final class ProfileUrls extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use GroupTrait;

    private const META_KEY = 'additional_profile_urls';

    public function get_column_type(): string
    {
        return 'column-rankmath-profile_urls';
    }

    public function get_label(): string
    {
        return __('Additional profile URLs', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\User\Meta(self::META_KEY))
                     ->add(new AC\Formatter\ExplodeToCollection(' '))
                     ->add(new AC\Formatter\Linkable());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\MultiInput())->set_clear_button(true)->set_sub_type('url'),
            new Editing\Storage\User\ProfileUrls()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta(new Operators([
            Operators::CONTAINS,
            Operators::NOT_CONTAINS,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ], false), self::META_KEY);
    }

}