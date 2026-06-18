<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Service;

use AC;
use AC\Registerable;
use ACA;
use ACA\WC\Search;
use ACA\WC\Service\HideSubscriptionsFilter;
use ACA\WC\Setting\TableElement\FilterSubscriptionCustomer;
use ACA\WC\Setting\TableElement\FilterSubscriptionPayment;
use ACA\WC\Setting\TableElement\FilterSubscriptionProduct;

final class TableScreen implements Registerable
{

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'hide_filters']);
    }

    public function hide_filters(AC\ListScreen $list_screen): void
    {
        $table_screen = $list_screen->get_table_screen();

        if ( ! $table_screen instanceof ACA\WC\Subscriptions\TableScreen\OrderSubscription) {
            return;
        }

        $services[] = new HideSubscriptionsFilter($list_screen, new FilterSubscriptionProduct());
        $services[] = new HideSubscriptionsFilter($list_screen, new FilterSubscriptionPayment());
        $services[] = new HideSubscriptionsFilter($list_screen, new FilterSubscriptionCustomer());

        foreach ($services as $service) {
            $service->register();
        }
    }

}