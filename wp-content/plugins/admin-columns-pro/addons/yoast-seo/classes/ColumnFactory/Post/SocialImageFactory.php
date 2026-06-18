<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post;

use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Meta\QueryMetaFactory;
use AC\MetaType;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\YoastSeo\Editing;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;

class SocialImageFactory extends ACP\Column\AdvancedColumnFactory
{

    private ImageSize $image;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ImageSize $image
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->image = $image;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->image->create($config));
    }

    protected function get_group(): ?string
    {
        return 'yoast-seo';
    }

    public function get_column_type(): string
    {
        return 'column-yoast_facebook_image';
    }

    public function get_label(): string
    {
        return __('Social Image', 'codepress-admin-columns');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Image())->set_clear_button(true),
            new Editing\Storage\Post\SocialImage('_yoast_wpseo_opengraph-image-id', '_yoast_wpseo_opengraph-image')
        );
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        $query_meta_factory = new QueryMetaFactory();

        return new ACP\Search\Comparison\Meta\Image(
            '_yoast_wpseo_opengraph-image-id',
            $query_meta_factory->create('_yoast_wpseo_opengraph-image-id', new MetaType(MetaType::POST))
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Meta('_yoast_wpseo_opengraph-image'));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(
            new Meta('_yoast_wpseo_opengraph-image-id')
        );
    }

}