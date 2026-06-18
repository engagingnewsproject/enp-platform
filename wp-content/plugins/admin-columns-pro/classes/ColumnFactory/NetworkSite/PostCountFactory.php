<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Formatter\NetworkSite\PostCount;
use ACP\Setting\ComponentFactory;
use ACP\Settings;

class PostCountFactory extends AdvancedColumnFactory
{

    private ComponentFactory\NetworkSite\PostType $post_type;

    private ComponentFactory\NetworkSite\PostStatus $post_status;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ComponentFactory\NetworkSite\PostType $post_type_component,
        ComponentFactory\NetworkSite\PostStatus $post_status
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->post_type = $post_type_component;
        $this->post_status = $post_status;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->post_type->create($config),
            $this->post_status->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new PostCount($config->get('post_type', ''), $config->get('post_status', '')),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-msite_postcount';
    }

    public function get_label(): string
    {
        return __('Post Count', 'codepress-admin-columns');
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

}