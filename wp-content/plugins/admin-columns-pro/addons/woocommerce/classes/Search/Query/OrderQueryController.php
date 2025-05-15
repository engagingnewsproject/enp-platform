<?php

namespace ACA\WC\Search\Query;

use AC;
use AC\Registerable;
use ACA\WC;
use WP_Screen;

final class OrderQueryController implements Registerable
{

    private bool $is_main_query = false;

    private const KEY = 'ac_is_main_order_query';

    private AC\ListScreenFactory\Aggregate $factory;

    public function __construct(AC\ListScreenFactory\Aggregate $factory)
    {
        $this->factory = $factory;
    }

    public function register(): void
    {
        add_action('current_screen', [$this, 'init']);
    }

    public function init(WP_Screen $screen): void
    {
        if ( ! $this->factory->can_create_from_wp_screen($screen)) {
            return;
        }

        $order = $this->factory->create_from_wp_screen($screen);

        if ( ! $order instanceof WC\ListScreen\Order && ! $order instanceof WC\ListScreen\OrderSubscription) {
            return;
        }

        $this->is_main_query = true;

        add_filter('woocommerce_order_list_table_prepare_items_query_args', [$this, 'set_main_query_true']);
        add_filter('woocommerce_order_query', [$this, 'set_main_query_false']);
        add_filter('woocommerce_order_query_args', [$this, 'check_if_main_query']);
    }

    public function set_main_query_true($value)
    {
        remove_filter("woocommerce_order_list_table_prepare_items_query_args", [$this, __FUNCTION__]);

        $this->is_main_query = true;

        return $value;
    }

    public function set_main_query_false($value)
    {
        $this->is_main_query = false;

        return $value;
    }

    public function check_if_main_query(array $args): array
    {
        if ($this->is_main_query) {
            $args[self::KEY] = true;
        }

        return $args;
    }

    public static function is_main_query(array $args): bool
    {
        return isset($args[self::KEY]) && $args[self::KEY];
    }

}