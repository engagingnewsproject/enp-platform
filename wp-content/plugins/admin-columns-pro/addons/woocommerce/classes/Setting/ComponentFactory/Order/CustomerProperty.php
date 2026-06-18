<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Formatter\User\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\OptionCollection;
use AC\Setting\Control\Type\Option;
use ACA\WC\Type\AddressType;
use ACA\WC\Value\Formatter;
use ACA\WC\Value\Formatter\WcFormattedCountry;
use ACP;

class CustomerProperty extends ACP\Setting\ComponentFactory\UserProperty
{

    public const PROPERTY_BILLING_ADDRESS = 'billing_address';
    public const PROPERTY_BILLING_COMPANY = 'billing_company';
    public const PROPERTY_BILLING_COUNTRY = 'billing_country';
    public const PROPERTY_BILLING_EMAIL = 'billing_email';
    public const PROPERTY_CUSTOMER_SINCE = 'customer_since';
    public const PROPERTY_ORDER_COUNT = 'order_count';
    public const PROPERTY_SHIPPING_ADDRESS = 'shipping_address';
    public const PROPERTY_TOTAL_SALES = 'total_sales';

    protected function get_input_options(): OptionCollection
    {
        $options = parent::get_input_options();

        $_options = [
            self::PROPERTY_BILLING_ADDRESS  => __('Billing Address', 'woocommerce'),
            self::PROPERTY_BILLING_COMPANY  => __('Billing Company', 'codepress-admin-columns'),
            self::PROPERTY_BILLING_COUNTRY  => __('Billing Country', 'codepress-admin-columns'),
            self::PROPERTY_BILLING_EMAIL    => __('Billing Email', 'codepress-admin-columns'),
            self::PROPERTY_CUSTOMER_SINCE   => __('Customer Since', 'codepress-admin-columns'),
            self::PROPERTY_ORDER_COUNT      => __('Order Count', 'codepress-admin-columns'),
            self::PROPERTY_SHIPPING_ADDRESS => __('Shipping Address', 'woocommerce'),
            self::PROPERTY_TOTAL_SALES      => __('Total Sales', 'codepress-admin-columns'),
        ];

        natcasesort($_options);

        foreach ($_options as $value => $label) {
            $options->add(new Option((string)$label, (string)$value, __('WooCommerce', 'codepress-admin-columns')));
        }

        return $options;
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        $property = $config->get(self::KEY, '');

        switch ($property) {
            case self::PROPERTY_BILLING_ADDRESS:
                $formatters->add(new Formatter\User\FullAddress(new AddressType(AddressType::BILLING)));
                break;
            case self::PROPERTY_BILLING_COMPANY:
                $formatters->add(new Meta('billing_company'));
                break;
            case self::PROPERTY_BILLING_COUNTRY:
                $formatters->add(new Meta('billing_country'));
                $formatters->add(new WcFormattedCountry());
                break;
            case self::PROPERTY_BILLING_EMAIL:
                $formatters->add(new Meta('billing_email'));
                break;
            case self::PROPERTY_CUSTOMER_SINCE:
                $formatters->add(new Formatter\User\FirstOrder());
                $formatters->add(new Formatter\Order\DateCreated());
                break;
            case self::PROPERTY_ORDER_COUNT:
                $formatters->add(new Formatter\User\OrderCount());
                $formatters->add(new Formatter\Order\FilterByCustomerLink());
                break;
            case self::PROPERTY_SHIPPING_ADDRESS:
                $formatters->add(new Formatter\User\FullAddress(new AddressType(AddressType::SHIPPING)));
                break;
            case self::PROPERTY_TOTAL_SALES:
                $formatters->add(new Formatter\User\TotalSales());
                break;
            default:
                parent::add_formatters($config, $formatters);
        }
    }

}