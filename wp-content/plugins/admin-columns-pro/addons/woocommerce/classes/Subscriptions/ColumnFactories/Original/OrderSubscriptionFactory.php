<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories\Original;

use AC\TableScreen;
use ACA\WC\Subscriptions;
use ACA\WC\Subscriptions\ColumnFactory\OrderSubscription;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class OrderSubscriptionFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof Subscriptions\TableScreen\OrderSubscription) {
            return [];
        }

        return [
            'end_date'          => OrderSubscription\Original\EndDate::class,
            'last_payment_date' => OrderSubscription\Original\LastOrderDate::class,
            'next_payment_date' => OrderSubscription\Original\NextPaymentDate::class,
            'order_items'       => OrderSubscription\Original\OrderItems::class,
            'order_title'       => OrderSubscription\Original\OrderTitle::class,
            'recurring_total'   => OrderSubscription\Original\RecurringTotal::class,
            'start_date'        => OrderSubscription\Original\StartDate::class,
            'status'            => OrderSubscription\Original\Status::class,
            'trial_end_date'    => OrderSubscription\Original\TrialEndDate::class,
        ];
    }
}