<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class IdFactory extends AdvancedColumnFactory
{

    private BeforeAfter $before_after;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        BeforeAfter $before_after
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->before_after = $before_after;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->before_after->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-group_id';
    }

    public function get_label(): string
    {
        return __('ID', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }
}