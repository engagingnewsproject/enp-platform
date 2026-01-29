<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\NumberOfItems;
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

class Orders extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private NumberOfItems $number_of_items;

    private OrderStatuses $order_statuses;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        NumberOfItems $number_of_items,
        OrderStatuses $order_statuses
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->number_of_items = $number_of_items;
        $this->order_statuses = $order_statuses;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->number_of_items->create($config))
                     ->add($this->order_statuses->create($config));
    }

    public function get_label(): string
    {
        return __('Orders', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-orders';
    }

    private function get_order_status(Config $config): array
    {
        return (array)$config->get('order_status', []);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\User\OrdersCollection($this->get_order_status($config)))
                     ->add(new Formatter\User\OrderInformation())
                     ->add(
                         new Separator(' |', (int)$config->get('number_of_items', 20))
                     );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\OrderCount($this->get_order_status($config));
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\User\OrdersCollection($this->get_order_status($config)),
            new Separator(', '),
        ]);
    }

}