<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Formatter\Message;
use AC\Formatter\Post\PostContent;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class Shortcode extends ACP\Column\AdvancedColumnFactory
{

    private ACP\Setting\ComponentFactory\Shortcodes $shortcodes;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ACP\Setting\ComponentFactory\Shortcodes $shortcodes
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->shortcodes = $shortcodes;
    }

    public function get_label(): string
    {
        return __('Shortcode', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-render_shortcode';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->shortcodes->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $shortcode = $config->get('shortcode', '');

        if ( ! $shortcode) {
            return new FormatterCollection([
                new Message(__('No shortcode selected', 'codepress-admin-columns')),
            ]);
        }

        return new FormatterCollection([
            new PostContent(),
            new ACP\Formatter\RenderShortcode($shortcode),
        ]);
    }

}