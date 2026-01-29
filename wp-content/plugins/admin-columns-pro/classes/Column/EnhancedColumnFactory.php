<?php

declare(strict_types=1);

namespace ACP\Column;

use AC;
use AC\Column\ColumnFactory;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use ACP;

class EnhancedColumnFactory extends ACP\Column\ColumnFactory
{

    private ColumnFactory $column_factory;

    private ?AC\Column $column = null;

    private ?string $hash = null;

    public function __construct(
        ColumnFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($feature_setting_builder_factory);

        $this->column_factory = $column_factory;
    }

    private function get_column(Config $config): AC\Column
    {
        $hash = hash('sha256', serialize($config->all()));

        if ($this->hash === null || $hash !== $this->hash) {
            $this->hash = $hash;
            $this->column = $this->column_factory->create($config);
        }

        return $this->column;
    }

    public function get_label(): string
    {
        return $this->column_factory->get_label();
    }

    public function get_column_type(): string
    {
        return $this->column_factory->get_column_type();
    }

    protected function get_formatters(Config $config): AC\FormatterCollection
    {
        return $this->get_column($config)->get_formatters();
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return $this->get_column($config)->get_settings();
    }

    public function create(Config $config): AC\Column
    {
        return new AdvancedColumn(
            $this->get_column_type(),
            $this->get_label(),
            $this->get_settings($config)
                 ->merge($this->get_feature_settings_builder($config)->build($config)),
            $this->get_column($config)->get_id(),
            $this->get_context($config),
            $this->get_formatters($config),
            $this->get_column($config)->get_group(),
            $this->get_sorting($config),
            $this->get_editing($config),
            $this->get_search($config),
            $this->get_export($config),
            $this->get_conditional_format($config)
        );
    }

}