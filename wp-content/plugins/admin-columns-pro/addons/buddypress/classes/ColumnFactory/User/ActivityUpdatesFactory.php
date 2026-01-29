<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP;
use ACA\BP\Settings\ComponentFactory\ActivityType;
use ACA\BP\Value\Formatter\User\ActivityUpdates;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Sorting;

class ActivityUpdatesFactory extends AdvancedColumnFactory
{

    private ActivityType $activity_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ActivityType $activity_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->activity_type = $activity_type;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->activity_type->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    public function get_column_type(): string
    {
        return 'column-buddypress_user_activity_updates';
    }

    public function get_label(): string
    {
        return __('Activity Updates', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ActivityUpdates($config->get('activity_type', '')),
        ]);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new BP\Sorting\User\ActivityUpdates($config->get('activity_type', ''));
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new BP\Search\User\ActivityUpdates($config->get('activity_type', ''));
    }

}