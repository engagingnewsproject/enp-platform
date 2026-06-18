<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post;

use AC\Formatter\Post\Meta;
use AC\Formatter\YesIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Type\ToggleOptions;
use ACP;

final class FacebookShowIconOverlay extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = 'rank_math_facebook_enable_image_overlay';

    public function get_column_type(): string
    {
        return 'column-rankmath-facebook_show_icon';
    }

    public function get_label(): string
    {
        return _x('Show Icon Overlay', 'Rank Math Facebook', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Meta(self::META_KEY))
                     ->add(new YesIcon());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Toggle(ToggleOptions::create_from_array(['' => 'False', 'on' => 'True'])),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta(self::META_KEY));
    }

    protected function get_group(): string
    {
        return 'rank-math-social-facebook';
    }

}