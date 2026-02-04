<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Formatter\Media\PostsHavingFeaturedImageCollection;
use ACP\Search;

class UsedAsFeaturedImage extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private ACP\Setting\ComponentFactory\Media\FeaturedImageDisplay $featured_image_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ACP\Setting\ComponentFactory\Media\FeaturedImageDisplay $featured_image_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->featured_image_display = $featured_image_display;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->featured_image_display->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-used_as_featured_image';
    }

    public function get_label(): string
    {
        return __('Featured Image', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'media-image';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new PostsHavingFeaturedImageCollection(),
        ]);

        switch ($config->get('featured_image_display')) {
            case 'count':
                $formatters->add(new AC\Formatter\Count());
                break;
            case 'title':
                $formatters->add(new AC\Formatter\Post\PostTitle());
                $formatters->add(new AC\Formatter\CharacterLimit(40));
                $formatters->add(new AC\Formatter\Post\PostLink('edit_post'));
                break;
            default:
                $formatters->add(new AC\Formatter\Count());
                $formatters->add(new AC\Formatter\YesNoIcon());
        }

        return $formatters;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        switch ($config->get('featured_image_display')) {
            case 'title':
                return parent::get_export($config);
            case 'count':
            default:
                return new FormatterCollection([
                    new PostsHavingFeaturedImageCollection(),
                    new AC\Formatter\Count(),
                ]);
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Media\UsedAsFeaturedImage();
    }

}