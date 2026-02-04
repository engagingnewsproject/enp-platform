<?php

declare(strict_types=1);

namespace ACA\WC\Admin;

use AC;
use AC\Admin\Type\MenuGroup;
use AC\PostType;
use AC\TableScreen;
use AC\Taxonomy;
use ACA\WC\Subscriptions\TableScreen\OrderSubscription;
use ACA\WC\TableScreen\Order;

class MenuGroupFactory implements AC\Admin\MenuGroupFactory
{

    public function create(TableScreen $table_screen): ?MenuGroup
    {
        switch (true) {
            case $table_screen instanceof Order :
            case $table_screen instanceof OrderSubscription :
                return new MenuGroup('woocommerce', __('WooCommerce'), 13, 'cpacicon-woo');
            case $table_screen instanceof PostType :
                if (in_array(
                    (string)$table_screen->get_post_type(),
                    ['product', 'shop_order', 'product_variation', 'shop_coupon'],
                    true
                )) {
                    return new MenuGroup('woocommerce', __('WooCommerce'), 13, 'cpacicon-woo');
                }

                break;
            case $table_screen instanceof Taxonomy :
                if (in_array((string)$table_screen->get_taxonomy(), ['product_tag', 'product_cat'], true)) {
                    return new MenuGroup('woocommerce-taxonomy', __('WooCommerce Taxonomies'), 13, 'cpacicon-woo');
                }

                if (taxonomy_is_product_attribute((string)$table_screen->get_taxonomy())) {
                    return new MenuGroup('woocommerce-attributes', __('WooCommerce Attributes'), 13, 'cpacicon-woo');
                }
                break;
        }

        return null;
    }

}