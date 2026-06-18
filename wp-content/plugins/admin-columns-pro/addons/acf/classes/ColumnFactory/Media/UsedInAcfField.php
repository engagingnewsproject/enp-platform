<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\Media;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\ACF\Formatter\Media\PostsContainingImageInAcfCollection;
use ACA\ACF\Formatter\Media\PostsContainingImageInAcfExtendedLink;
use ACA\ACF\Helper\Media\PostsContainingImageInAcfFinder;
use ACA\ACF\Setting\ComponentFactory\Media\UsedInAcfFieldDisplay;
use ACA\ACF\Value\ExtendedValue\Media\PostsContainingImageInAcf;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class UsedInAcfField extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private UsedInAcfFieldDisplay $display_setting;

    private PostsContainingImageInAcf $extended_value;

    private PostsContainingImageInAcfFinder $finder;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        UsedInAcfFieldDisplay $display_setting,
        PostsContainingImageInAcf $extended_value,
        PostsContainingImageInAcfFinder $finder
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->display_setting = $display_setting;
        $this->extended_value = $extended_value;
        $this->finder = $finder;
    }

    public function get_column_type(): string
    {
        return 'column-used_in_acf_field';
    }

    public function get_label(): string
    {
        return __('Used in ACF Field', 'codepress-admin-columns');
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
            new PostsContainingImageInAcfCollection($this->finder),
        ]);

        switch ($config->get('used_in_acf_field_display')) {
            case 'count':
                $formatters->add(new PostsContainingImageInAcfExtendedLink($this->extended_value));
                break;
            default: // 'true_false'
                $formatters->add(new AC\Formatter\Count());
                $formatters->add(new AC\Formatter\YesNoIcon());
        }

        return $formatters;
    }

}
