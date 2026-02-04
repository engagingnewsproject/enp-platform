<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\UserProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP\Value\Formatter\Group\CreatorId;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class CreatorFactory extends AdvancedColumnFactory
{

    private UserProperty $user_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        UserProperty $user_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->user_property = $user_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->user_property->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-group_creator';
    }

    public function get_label(): string
    {
        return __('Creator', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return (new FormatterCollection([
            new CreatorId(),
        ]))->merge(parent::get_formatters($config));
    }

}