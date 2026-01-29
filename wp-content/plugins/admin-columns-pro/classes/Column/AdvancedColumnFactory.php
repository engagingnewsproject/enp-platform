<?php

declare(strict_types=1);

namespace ACP\Column;

use AC\Column;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;

abstract class AdvancedColumnFactory extends ColumnFactory
{

    use Column\GroupTrait;

    private DefaultSettingsBuilder $default_settings_builder;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder
    ) {
        $this->default_settings_builder = $default_settings_builder;

        parent::__construct($feature_setting_builder_factory);
    }

    public function create(Config $config): Column
    {
        $column_id_generator = new Column\ColumnIdGenerator();

        $settings = $this->default_settings_builder
            ->build($config)
            ->merge($this->get_settings($config))
            ->merge(
                $this->get_feature_settings_builder($config)->build($config)
            );

        return new AdvancedColumn(
            $this->get_column_type(),
            $this->get_label(),
            $settings,
            $column_id_generator->from_config($config),
            $this->get_context($config),
            $this->get_formatters($config),
            $this->get_group(),
            $this->get_sorting($config),
            $this->get_editing($config),
            $this->get_search($config),
            $this->get_export($config),
            $this->get_conditional_format($config)
        );
    }

}