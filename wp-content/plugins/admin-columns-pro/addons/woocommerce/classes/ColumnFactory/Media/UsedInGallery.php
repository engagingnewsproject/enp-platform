<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Media;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\Formatter\Media\PostsContainingImageInGalleryCollection;
use ACA\WC\Helper\Media\PostsContainingImageInGalleryFinder;
use ACA\WC\Setting\ComponentFactory\Media\UsedInGalleryDisplay;
use ACA\WC\Value\ExtendedValue\Media\PostsContainingImageInGallery;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Formatter\Media\PostsContainingImageExtendedLink;

class UsedInGallery extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private UsedInGalleryDisplay $display_setting;

    private PostsContainingImageInGallery $extended_value;

    private PostsContainingImageInGalleryFinder $finder;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        UsedInGalleryDisplay $display_setting,
        PostsContainingImageInGallery $extended_value,
        PostsContainingImageInGalleryFinder $finder
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->display_setting = $display_setting;
        $this->extended_value = $extended_value;
        $this->finder = $finder;
    }

    public function get_column_type(): string
    {
        return 'column-used_in_gallery';
    }

    public function get_label(): string
    {
        return __('Used in Product Gallery', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'media-image';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->display_setting->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new PostsContainingImageInGalleryCollection($this->finder),
        ]);

        switch ($config->get('used_in_gallery_display')) {
            case 'count':
                $formatters->add(new PostsContainingImageExtendedLink(
                    $this->extended_value,
                    fn(int $count): string => sprintf(
                        _n('%d product', '%d products', $count, 'codepress-admin-columns'),
                        $count
                    )
                ));
                break;
            default: // 'true_false'
                $formatters->add(new AC\Formatter\Count());
                $formatters->add(new AC\Formatter\YesNoIcon());
        }

        return $formatters;
    }

}
