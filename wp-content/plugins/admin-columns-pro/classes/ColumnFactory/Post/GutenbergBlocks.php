<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Setting\ComponentFactory\GutenbergDisplay;

class GutenbergBlocks extends AdvancedColumnFactory
{

    private GutenbergDisplay $gutenberg_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        GutenbergDisplay $gutenberg_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->gutenberg_display = $gutenberg_display;
    }

    public function get_column_type(): string
    {
        return 'column-post_gutenberg_blocks';
    }

    public function get_label(): string
    {
        return __('Gutenberg Blocks', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\Post\PostContent());
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->gutenberg_display->create($config),
        ]);
    }

}