<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post;

use AC\Formatter\MapOptionLabel;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use RankMath\Helper;

final class FacebookIconOverlay extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = 'rank_math_facebook_image_overlay';

    public function get_column_type(): string
    {
        return 'column-rankmath-facebook_icon_overlay';
    }

    private function get_display_options(): array
    {
        $options = [];

        foreach (Helper::choices_overlay_images() as $key => $option) {
            $options[$key] = $option['name'];
        }

        return $options;
    }

    public function get_label(): string
    {
        return _x('Icon Overlay', 'Rank Math Facebook', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Meta(self::META_KEY))
                     ->add(new MapOptionLabel($this->get_display_options()));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(self::META_KEY, $this->get_display_options());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_display_options()),
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