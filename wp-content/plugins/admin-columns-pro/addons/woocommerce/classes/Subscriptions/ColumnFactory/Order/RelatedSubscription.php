<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\Setting\ComponentFactory\Order\OrderProperty;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class RelatedSubscription extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use SubscriptionGroupTrait;

    private OrderProperty $order_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        OrderProperty $order_property
    ) {
        parent::__construct($feature_setting_builder_factory, $default_settings_builder);
        $this->order_property = $order_property;
    }

    public function get_label(): string
    {
        return __('Related Subscription', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->order_property->create($config));
    }

    public function get_column_type(): string
    {
        return 'column_wc_related_subscription';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Order\RelatedSubscription());
    }

}