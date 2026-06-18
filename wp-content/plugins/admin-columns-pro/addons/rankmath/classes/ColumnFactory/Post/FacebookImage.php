<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post;

use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\RankMath\Editing;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;

final class FacebookImage extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    private const META_KEY = 'rank_math_facebook_image';
    private const META_KEY_ID = 'rank_math_facebook_image_id';

    private ImageSize $image_size;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ImageSize $image_size
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->image_size = $image_size;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->image_size->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-rankmath-facebook_image';
    }

    public function get_label(): string
    {
        return _x('Image', 'Rank Math Facebook', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Meta(self::META_KEY_ID));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Image(),
            new Editing\Storage\Post\FacebookImage()
        );
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