<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Product;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Search;
use ACP;

class LimitSubscription extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Limit subscription', 'woocommerce-subscriptions');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription-limit';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new AC\Formatter\Post\Meta('_subscription_limit'))
                     ->add(new AC\Formatter\MapOptionLabel($this->get_limit_options()));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductSubscription\Limit($this->get_limit_options());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_subscription_limit');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductSubscription\Options(
            '_subscription_limit',
            $this->get_limit_options()
        );
    }

    private function get_limit_options(): array
    {
        return [
            'no'     => __('Do not limit', 'woocommerce-subscriptions'),
            'active' => __('Limit to one active subscription', 'woocommerce-subscriptions'),
            'any'    => __('Limit to one of any status', 'woocommerce-subscriptions'),
        ];
    }

}