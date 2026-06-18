<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\UserLinkFactory;
use AC\Setting\ComponentFactory\UserProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\CustomerProperty;
use ACA\WC\Sorting;
use ACA\WC\Sorting\Order\CustomerField;
use ACA\WC\Sorting\Order\CustomerFullname;
use ACA\WC\Sorting\Order\OrderData;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class CustomerFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private CustomerProperty $customer_property;

    private UserLinkFactory $user_link_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        CustomerProperty $customer_property,
        UserLinkFactory $user_link_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->customer_property = $customer_property;
        $this->user_link_factory = $user_link_factory;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        $settings = parent::get_settings($config)
                          ->add($this->customer_property->create($config));

        if ($config->get('display_author_as', '') !== CustomerProperty::PROPERTY_CUSTOMER_SINCE) {
            $settings->add($this->user_link_factory->create()->create($config));
        }

        return $settings;
    }

    public function get_label(): string
    {
        return __('Customer', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_customer';
    }

    private function get_display_property(Config $config): string
    {
        return $config->get('display_author_as', '');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Formatter\Order\CustomerId());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($this->get_display_property($config)) {
            case UserProperty::PROPERTY_FIRST_NAME:
            case UserProperty::PROPERTY_LAST_NAME:
            case UserProperty::PROPERTY_NICKNAME :
            case UserProperty::PROPERTY_NICENAME :
                return new Search\Order\Customer\UserMeta($this->get_display_property($config));
            case UserProperty::PROPERTY_LOGIN :
            case UserProperty::PROPERTY_URL :
            case UserProperty::PROPERTY_EMAIL :
            case UserProperty::PROPERTY_DISPLAY_NAME :
                return new Search\Order\Customer\UserField($this->get_display_property($config));
            case UserProperty::PROPERTY_ID :
                return new Search\Order\Customer\UserId();
            case UserProperty::PROPERTY_FULL_NAME :
            default:
                return null;
        }
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        switch ($this->get_display_property($config)) {
            case UserProperty::PROPERTY_FIRST_NAME:
            case UserProperty::PROPERTY_LAST_NAME:
                return new CustomerField($this->get_display_property($config));
            case UserProperty::PROPERTY_EMAIL:
                return new CustomerField('email');
            case UserProperty::PROPERTY_NICKNAME:
            case UserProperty::PROPERTY_LOGIN:
                return new CustomerField('username');
            case UserProperty::PROPERTY_FULL_NAME:
                return new CustomerFullname();
            case UserProperty::PROPERTY_ID:
                return new OrderData('customer_id');
            default:
                return null;
        }
    }

}