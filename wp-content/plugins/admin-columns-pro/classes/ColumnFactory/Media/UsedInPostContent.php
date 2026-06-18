<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Formatter\Media\PostsContainingImageExtendedLink;
use ACP\Formatter\Media\PostsContainingImageInContentCollection;
use ACP\Helper\Media\PostsContainingImageFinder;
use ACP\Setting\ComponentFactory\Media\UsedInPostContentDisplay;
use ACP\Value\ExtendedValue\Media\PostsContainingImage;
use AC\Setting\DefaultSettingsBuilder;

class UsedInPostContent extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private UsedInPostContentDisplay $used_in_post_content_display;

    private PostsContainingImage $extended_value;

    private PostsContainingImageFinder $finder;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        UsedInPostContentDisplay $used_in_post_content_display,
        PostsContainingImage $extended_value,
        PostsContainingImageFinder $finder
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->used_in_post_content_display = $used_in_post_content_display;
        $this->extended_value = $extended_value;
        $this->finder = $finder;
    }

    public function get_column_type(): string
    {
        return 'column-used_in_post_content';
    }

    public function get_label(): string
    {
        return __('Used in Post Content', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'media-image';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->used_in_post_content_display->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new PostsContainingImageInContentCollection($this->finder),
        ]);

        switch ($config->get('used_in_post_content_display')) {
            case 'count':
                $formatters->add(new PostsContainingImageExtendedLink($this->extended_value));
                break;
            default: // 'true_false'
                $formatters->add(new AC\Formatter\Count());
                $formatters->add(new AC\Formatter\YesNoIcon());
        }

        return $formatters;
    }

}
