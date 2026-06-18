<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\IntegerFormatter;
use ACP\Formatter\NetworkSite\PluginCollection;
use ACP\Formatter\NetworkSite\SwitchBlog;
use ACP\Formatter\Plugin\CountWithTooltip;
use ACP\Formatter\Plugin\PluginLink;
use ACP\Formatter\Plugin\PluginName;
use ACP\Setting\ComponentFactory\NetworkSite\PluginDisplay;
use ACP\Setting\ComponentFactory\NetworkSite\PluginIncludeNetwork;
use ACP\Value\ExtendedValue\NetworkSites\Plugins;

class PluginsFactory extends AdvancedColumnFactory
{

    private PluginIncludeNetwork $plugin_include_network;

    private PluginDisplay $plugin_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        PluginIncludeNetwork $plugin_include_network,
        PluginDisplay $plugin_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->plugin_include_network = $plugin_include_network;
        $this->plugin_display = $plugin_display;
    }

    public function get_column_type(): string
    {
        return 'column-msite_plugins';
    }

    public function get_label(): string
    {
        return __('Plugins');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->plugin_include_network->create($config),
            $this->plugin_display->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = [
            new PluginCollection($config->get('include_network') === 'on'),
            new PluginName(),
        ];

        switch ($config->get('plugin_display', '')) {
            case 'list':
                $formatters[] = new PluginLink();
                $formatters[] = new Separator('<br>');
                break;
            default:
                $formatters[] = new CountWithTooltip(
                    new Plugins()
                );
        }

        return FormatterCollection::from_formatter(
            new SwitchBlog($formatters)
        );
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        $formatter = $config->get('plugin_display', '') === 'count'
            ? new IntegerFormatter()
            : null;

        return new FormattableConfig($formatter);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }
}