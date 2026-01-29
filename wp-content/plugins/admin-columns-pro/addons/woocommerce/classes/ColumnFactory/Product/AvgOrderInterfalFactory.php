<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat;
use ACA\WC\Setting\ComponentFactory\Period;
use ACA\WC\Value\Formatter\HumanTimeDifference;
use ACA\WC\Value\Formatter\Product\AvgOrderInterval;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;

class AvgOrderInterfalFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private Period $period;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        Period $period
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->period = $period;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->period->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-avg_order_interval';
    }

    public function get_label(): string
    {
        return __('Average Order Interval', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AvgOrderInterval($this->get_period_in_days($config)))
                     ->add(new HumanTimeDifference());
    }

    private function get_period_in_days(Config $config): int
    {
        return (int)$config->get('period', 365);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new AvgOrderInterval($this->get_period_in_days($config)),
        ]);
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ConditionalFormat\Formatter\Product\AvgOrderIntervalFormatter(
                new AvgOrderInterval($this->get_period_in_days($config))
            )
        );
    }

}