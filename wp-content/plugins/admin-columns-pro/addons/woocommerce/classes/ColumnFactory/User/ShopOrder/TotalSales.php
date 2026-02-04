<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\OrderStatuses;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class TotalSales extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private OrderStatuses $order_statuses;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        OrderStatuses $order_statuses
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->order_statuses = $order_statuses;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->order_statuses->create($config));
    }

    public function get_label(): string
    {
        return __('Total Sales', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-total-sales';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\User\ShopOrder\TotalSales($config->get('order_status', [])));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\ShopOrder\TotalSales($config->get('order_status', []));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\ShopOrder\TotalSales((array)$config->get('order_status', []));
    }

}