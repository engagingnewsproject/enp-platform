<?php

declare(strict_types=1);

namespace ACA\WC\TableScreen;

use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use Automattic\WooCommerce\Internal\Admin\Orders\PageController;
use WP_Screen;

/**
 * The HPOS Order table
 */
class OrderFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return new Order();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId('wc_order'));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return new Order();
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        $action = $_GET['action'] ?? null;
        $order = $_GET['order'] ?? null;

        return 'woocommerce_page_wc-orders' === $screen->base
               && 'woocommerce_page_wc-orders' === $screen->id
               && 'trash' !== $action
               && ! is_array($order)
               && wc_get_container()->get(PageController::class)->is_order_screen('shop_order', 'list');
    }

}