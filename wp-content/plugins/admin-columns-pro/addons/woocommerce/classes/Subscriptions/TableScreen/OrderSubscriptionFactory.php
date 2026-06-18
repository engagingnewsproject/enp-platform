<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\TableScreen;

use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use WP_Screen;

class OrderSubscriptionFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return new OrderSubscription();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId('wc_order_subscription'));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return new OrderSubscription();
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'woocommerce_page_wc-orders--shop_subscription' === $screen->base &&
               'woocommerce_page_wc-orders--shop_subscription' === $screen->id &&
               wc_get_container()->get(PageController::class)->is_order_screen('shop_subscription', 'list');
    }

}