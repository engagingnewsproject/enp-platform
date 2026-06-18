<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class OrderEmailsFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private AC\Setting\ComponentFactory\Separator $separator;

    public function __construct(
        DefaultSettingsBuilder $default_settings_builder,
        AC\Setting\ComponentFactory\Separator $separator,
        FeatureSettingBuilderFactory $feature_setting_builder_factory
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->separator = $separator;
    }

    public function get_label(): string
    {
        return __('Order Emails', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-order_emails';
    }

    public function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\OrderBillingEmails();
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->separator->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\User\OrderEmails())
                     ->add(AC\Formatter\Collection\Separator::create_from_config($config));
    }

}
