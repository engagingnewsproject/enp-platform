<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\Product;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use ACA\WC\Type\OrderTableUrl;
use Automattic\WooCommerce\Utilities\OrderUtil;
use DateTime;

class Customers implements ExtendedValue
{

    public function can_render(string $view): bool
    {
        return $view === 'product-customers';
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $users = $this->get_user_items_by_product($id);

        if ( ! $users) {
            return '';
        }

        $view = new AC\View([
            'items' => $users,
        ]);

        return $view->set_template('modal-value/customers')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, 'product-customers'))
            ->with_class('-nopadding -w-large');
    }

    private function get_user_items_by_product(int $id): array
    {
        $users = [];

        foreach ($this->get_customers_by_product($id, 50) as $customer_id => $orders) {
            $user = get_userdata($customer_id);

            if ( ! $user || ! $user->ID) {
                continue;
            }

            $name = ac_helper()->user->get_formatted_name($user);

            $edit_user = get_edit_user_link($user->ID);

            if ($edit_user) {
                $name = sprintf(
                    '<a href="%s">%s</a>',
                    $edit_user,
                    $name
                );
            }

            $count = count($orders);
            $count = sprintf(_n('%d order', '%d orders', $count, 'codepress-admin-columns'), $count);
            $count = sprintf(
                '<a href="%s">%s</a>',
                (new OrderTableUrl())->with_arg('_customer_user', (string)$customer_id),
                $count
            );

            $recent_order = reset($orders);
            $date = $recent_order['date'];
            $date = sprintf(
                '<a href="%s">%s</a>',
                OrderUtil::get_order_admin_edit_url((int)$recent_order['id']),
                date_i18n(get_option('date_format'), $date->getTimeStamp(), true)
            );

            $users[] = [
                'id'           => sprintf('#%s', $user->ID),
                'name'         => $name,
                'orders'       => $count,
                'recent_order' => $date,
            ];
        }

        return $users;
    }

    private function get_customers_by_product(int $id, ?int $limit = null): array
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
            SELECT o.customer_id, o.id, o.date_created_gmt
            FROM {$wpdb->prefix}wc_orders as o 
            INNER JOIN {$wpdb->prefix}wc_order_product_lookup opl
                ON o.id = opl.order_id AND opl.product_id = %d
            WHERE
                o.type = 'shop_order'
                AND o.status = %s
                AND o.customer_id > 0
            ORDER BY o.date_created_gmt DESC
        ",
            $id,
            'wc-completed'
        );

        if ($limit) {
            $sql .= "LIMIT $limit";
        }

        $customers = [];

        foreach ($wpdb->get_results($sql) as $row) {
            if (isset($customers[$row->customer_id][$row->id])) {
                continue;
            }
            $customers[$row->customer_id][$row->id] = [
                'date' => DateTime::createFromFormat('Y-m-d H:i:s', $row->date_created_gmt),
                'id'   => $row->id,
            ];
        }

        return $customers;
    }

}