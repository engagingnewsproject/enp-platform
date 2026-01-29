<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Meta\QueryMetaFactory;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACA\YoastSeo;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Search;

class TwitterImageFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY_IMAGE = '_yoast_wpseo_twitter-image-id';
    private const META_KEY_URL = '_yoast_wpseo_twitter-image';

    private ImageSize $image_size;

    private PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ImageSize $image_size,
        PostTypeSlug $post_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->image_size = $image_size;
        $this->post_type = $post_type;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->image_size->create($config));
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_twitter_image';
    }

    public function get_label(): string
    {
        return __('Twitter Image', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Meta(self::META_KEY_IMAGE));
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Image())->set_clear_button(true),
            new YoastSeo\Editing\Storage\Post\SocialImage(self::META_KEY_IMAGE, self::META_KEY_URL)
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta(self::META_KEY_URL));
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        $query_meta_factory = new QueryMetaFactory();

        return new ACP\Search\Comparison\Meta\Image(
            self::META_KEY_IMAGE,
            $query_meta_factory->create_with_post_type(self::META_KEY_IMAGE, (string)$this->post_type)
        );
    }

}