<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\ShippingMethodType;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ShippingMethodFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private ShippingMethodType $shipping_method_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ShippingMethodType $shipping_method_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->shipping_method_type = $shipping_method_type;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->shipping_method_type->create($config));
    }

    public function get_label(): string
    {
        return __('Shipping Method', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-order_shipping_method';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        switch ($this->get_shipping_method_type($config)) {
            case ShippingMethodType::METHOD_ID:
                $formatters->add(new Formatter\Order\ShippingMethodIds());
                break;
            case ShippingMethodType::METHOD_TITLE:
                $formatters->add(new Formatter\Order\ShippingMethodTitles());
                break;
        }

        return $formatters;
    }

    private function get_shipping_method_type(Config $config): string
    {
        return $config->get(ShippingMethodType::NAME, ShippingMethodType::METHOD_TITLE);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return ShippingMethodType::METHOD_TITLE === $this->get_shipping_method_type($config)
            ? new Search\Order\ShippingMethodLabel()
            : new Search\Order\ShippingMethod();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return ShippingMethodType::METHOD_TITLE === $this->get_shipping_method_type($config)
            ? new Sorting\Order\ShippingMethodLabel()
            : new Sorting\Order\ShippingMethod();
    }

}