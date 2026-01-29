<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\TableScreen;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\TableScreen;
use AC\Type\TableId;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        if ($table_screen->get_id()->equals(new TableId('shop_subscription'))) {
            return new MenuGroup('woocommerce', __('WooCommerce'), 13);
        }
        if ($table_screen->get_id()->equals(new TableId('wc_order_subscription'))) {
            return new MenuGroup('woocommerce', __('WooCommerce'), 13);
        }

        return null;
    }

}