<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\OrderStatusesFactory;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class SalesFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    private OrderStatusesFactory $order_statuses_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        OrderStatusesFactory $order_statuses_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->order_statuses_factory = $order_statuses_factory;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add(
            $this->order_statuses_factory->create(['wc-completed'])->create($config)
        );
    }

    public function get_column_type(): string
    {
        return 'column-wc-product_sales';
    }

    public function get_label(): string
    {
        return __('Products Sold', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new Formatter\Product\ShopOrder\ProductsSold((array)$config->get('order_status', ['wc-completed']))
        );
    }

}