<?php

declare(strict_types=1);

namespace ACP\Column;

use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;

class OriginalColumnFactory extends AdvancedColumnFactory
{

    private string $type;

    private string $label;

    private bool $add_sort;

    private bool $add_export;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        ?bool $add_sort = true,
        ?bool $add_export = true
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->type = $type;
        $this->label = $label;
        $this->add_sort = $add_sort;
        $this->add_export = $add_export;
    }

    protected function get_group(): ?string
    {
        return 'default';
    }

    public function get_label(): string
    {
        return $this->label;
    }

    public function get_column_type(): string
    {
        return $this->type;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return null;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);

        if ($this->add_sort) {
            $builder->set_sort();
        }

        if ($this->add_export) {
            $builder->set_export();
        }

        return $builder;
    }

}