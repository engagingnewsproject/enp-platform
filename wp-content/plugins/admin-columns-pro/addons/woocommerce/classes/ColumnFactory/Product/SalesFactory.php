<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\OrderStatusesFactory;
use ACA\WC\Setting\ComponentFactory\PeriodWithAllTime;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search\Comparison;

class SalesFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    private OrderStatusesFactory $order_statuses_factory;

    private PeriodWithAllTime $period;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        OrderStatusesFactory $order_statuses_factory,
        PeriodWithAllTime $period
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->order_statuses_factory = $order_statuses_factory;
        $this->period = $period;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->order_statuses_factory->create(['wc-completed'])->create($config))
                     ->add($this->period->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-product_sales';
    }

    public function get_label(): string
    {
        return __('Products Sold', 'codepress-admin-columns');
    }

    public function get_description(): ?string
    {
        return __('Total quantity of this product sold across all matching orders.', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $statuses = (array)$config->get('order_status', []);

        return parent::get_formatters($config)
                     ->prepend(new Formatter\Product\ProductsSold($statuses, $this->get_period_in_days($config)));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\Order\ProductsSold(
            (array)$config->get('order_status', ['wc-completed']),
            $this->get_period_in_days($config)
        );
    }

    protected function get_search(Config $config): ?Comparison
    {
        return new Search\Product\Order\ProductsSold(
            (array)$config->get('order_status', ['wc-completed']),
            $this->get_period_in_days($config)
        );
    }

    private function get_period_in_days(Config $config): ?int
    {
        $period = $config->get('period', '');

        if ('' === $period) {
            return null;
        }

        if ('custom' === $period) {
            $custom = (int)$config->get('period_custom', 0);

            return $custom > 0 ? $custom : null;
        }

        return (int)$period;
    }

}
