<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories\Original;

use AC\TableScreen;
use AC\Type\TableId;
use ACA\WC\Subscriptions\ColumnFactory\ShopSubscription;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class ShopSubscriptionFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen->get_id()->equals(new TableId('shop_subscription'))) {
            return [];
        }

        return [
            'end_date'          => ShopSubscription\Original\EndDate::class,
            'last_payment_date' => ShopSubscription\Original\LastPaymentDate::class,
            'next_payment_date' => ShopSubscription\Original\NextPaymentDate::class,
            'order_items'       => ShopSubscription\Original\OrderItems::class,
            'orders'            => ShopSubscription\Original\Orders::class,
            'recurring_total'   => ShopSubscription\Original\RecurringTotal::class,
            'start_date'        => ShopSubscription\Original\StartDate::class,
            'status'            => ShopSubscription\Original\Status::class,
            'trial_end_date'    => ShopSubscription\Original\TrialEndDate::class,
        ];
    }
}