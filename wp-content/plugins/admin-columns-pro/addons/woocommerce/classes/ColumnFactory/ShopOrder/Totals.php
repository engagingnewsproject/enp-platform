<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\PriceFormatter;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\TotalProperty;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class Totals extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private TotalProperty $total_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        TotalProperty $total_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->total_property = $total_property;
    }

    public function get_column_type(): string
    {
        return 'column-wc-order_totals';
    }

    public function get_label(): string
    {
        return __('Totals', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->total_property->create($config));
    }

    public function get_meta_key(Config $config): ?string
    {
        switch ($config->get(TotalProperty::NAME, 'total')) {
            case 'total' :
                return '_order_total';
            case 'discount' :
                return '_cart_discount';
            case 'shipping' :
                return '_order_shipping';
            default:
                return null;
        }
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        switch ($config->get(TotalProperty::NAME, 'total')) {
            case 'fees':
                $formatters->add(new Formatter\Order\TotalFees());
                break;
            case 'subtotal':
                $formatters->add(new Formatter\Order\SubTotal(false));
                break;
            case 'discount':
                $formatters->add(new Formatter\Order\TotalDiscount());
                break;
            case 'refund':
                $formatters->add(new Formatter\Order\TotalRefunds());
                break;
            case 'tax':
                $formatters->add(new Formatter\Order\TotalTax());
                break;
            case 'shipping':
                $formatters->add(new Formatter\Order\TotalShipping());
                break;
            case 'paid':
                $formatters->add(new Formatter\Order\TotalPaid());
                break;
            default:
                $formatters->add(new Formatter\Order\Total());
        }

        $formatters->add(new Formatter\WcPrice());

        return $formatters;
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new PriceFormatter()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $meta_key = $this->get_meta_key($config);

        return $meta_key
            ? new ACP\Sorting\Model\Post\Meta($meta_key)
            : null;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $meta_key = $this->get_meta_key($config);

        return $meta_key
            ? new ACP\Search\Comparison\Meta\Decimal($meta_key)
            : null;
    }

}