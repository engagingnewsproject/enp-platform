<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP;
use ACA\BP\Value\Formatter\Group\GroupProperty;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;

class NameOnlyFactory extends AdvancedColumnFactory
{

    private BeforeAfter $before_after;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        BeforeAfter $before_after
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->before_after = $before_after;
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->before_after->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-group_name';
    }

    public function get_label(): string
    {
        return __('Name Only', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->prepend(new GroupProperty('name'));

        return $formatters;
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new BP\Editing\Service\Group\NameOnly();
    }

}