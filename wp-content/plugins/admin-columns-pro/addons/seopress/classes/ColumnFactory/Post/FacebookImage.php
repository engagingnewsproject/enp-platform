<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\SeoPress\Editing;
use ACA\SeoPress\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

final class FacebookImage extends ACP\Column\AdvancedColumnFactory
{

    private ImageSize $image_size;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ImageSize $image_size
    ) {
        parent::__construct(
            $feature_setting_builder_factory,
            $default_settings_builder
        );
        $this->image_size = $image_size;
    }

    protected function get_group(): ?string
    {
        return 'seopress_social';
    }

    public function get_label(): string
    {
        return __('Facebook Thumbnail', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-sp_social_facebook_thumb';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->image_size->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\FacebookImage());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Meta('_seopress_social_fb_img'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\FacebookImage();
    }

}