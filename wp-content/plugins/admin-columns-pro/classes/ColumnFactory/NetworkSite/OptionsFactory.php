<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Formatter\NetworkSite\SiteOption;
use ACP\Setting\ComponentFactory\NetworkSite\SiteOptions;

class OptionsFactory extends AdvancedColumnFactory
{

    private SiteOptions $site_options;

    private BeforeAfter $before_after_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        SiteOptions $site_options,
        BeforeAfter $before_after_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->before_after_factory = $before_after_factory;
        $this->site_options = $site_options;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->site_options->create($config),
            $this->before_after_factory->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-msite_options';
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            new Editing\View\Text(),
            new Editing\Storage\Site\Option($config->get('field', ''))
        );
    }

    public function get_label(): string
    {
        return __('Options', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new SiteOption($config->get('field', '')),
        ]);

        return $formatters->merge(parent::get_formatters($config));
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}