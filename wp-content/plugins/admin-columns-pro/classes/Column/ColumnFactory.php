<?php

declare(strict_types=1);

namespace ACP\Column;

use AC\Column;
use AC\Formatter\Collection\Separator;
use AC\Formatter\StringSanitizer;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;

abstract class ColumnFactory extends Column\ColumnFactory
{

    private FeatureSettingBuilderFactory $feature_setting_builder_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        $this->feature_setting_builder_factory = $feature_setting_builder_factory;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = $this->feature_setting_builder_factory->create();

        if ($this->get_editing($config)) {
            $builder = $builder->set_edit();
        }

        if ($this->get_search($config)) {
            $builder = $builder->set_search();
        }

        if ($this->get_sorting($config)) {
            $builder = $builder->set_sort();
        }

        if ($this->get_export($config)) {
            $builder = $builder->set_export();
        }

        return $builder;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return null;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return null;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return null;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return $this->get_formatters($config)
                    ->with_formatter(new StringSanitizer())
                    ->with_formatter(new Separator());
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return null;
    }

}