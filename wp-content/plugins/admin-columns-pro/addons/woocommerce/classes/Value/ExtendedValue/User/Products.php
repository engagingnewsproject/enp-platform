<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\User;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;

class Products implements ExtendedValue
{

    private const NAME = 'user-products';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $products = $this->get_ordered_items($id);

        if (count($products) < 1) {
            return '';
        }

        $items = $quantity = [];

        foreach ($products as $row) {
            if ( ! $row->pid || $row->qty < 0) {
                continue;
            }

            $quantity[$row->pid] += $row->qty;

            if (isset($items[$row->pid])) {
                continue;
            }

            $ids = explode('#', $row->pid);

            $product_id = $ids[1] !== '0'
                ? $ids[1]
                : $ids[0];

            $title = get_the_title($product_id);
            $edit = get_edit_post_link($product_id);
            $removed = null === get_post($product_id);

            if ($edit) {
                $title = sprintf('<a href="%s">%s</a>', $edit, $title);
            }
            if ($removed) {
                $title = sprintf('%s #%d', __('Product removed', 'codepress-admin-columns'), $product_id);
            }

            $items[$row->pid] = [
                'title'   => $title,
                'removed' => $removed,
            ];
        }

        // Add total quantity to items
        foreach ($items as $pid => $item) {
            $items[$pid]['qty'] = $quantity[$pid] > 0
                ? sprintf('× %s', $quantity[$pid])
                : '-';
        }

        usort($items, [$this, 'sort']);

        $message = '';
        $limit = 100;

        if (count($items) > $limit) {
            $message = sprintf(
                '%s %s',
                __('Too many products.', 'codepress-admin-columns'),
                sprintf(__('Only the top %d are shown.', 'codepress-admin-columns'), $limit)
            );
            $items = array_slice($items, 0, $limit);
        }

        $view = new AC\View([
            'items'   => $items,
            'message' => $message,
        ]);

        return $view->set_template('modal-value/ordered-products')->render();
    }

    public function sort(array $a, array $b): int
    {
        return $b['total'] <=> $a['total'];
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-nopadding');
    }

    private function get_ordered_items(int $user_id): array
    {
        global $wpdb;

        $statuses = array_map('esc_sql', wc_get_is_paid_statuses());
        $statuses_sql = "( 'wc-" . implode("','wc-", $statuses) . "' )";

        $sql = $wpdb->prepare(
            "
            SELECT CONCAT( wcopl.product_id, '#', wcopl.variation_id ) as pid, SUM( wcopl.product_qty ) as qty
            FROM {$wpdb->prefix}wc_orders AS wco
            LEFT JOIN {$wpdb->prefix}wc_order_product_lookup AS wcopl ON wcopl.order_id = wco.id
            WHERE wco.customer_id = %d
                AND wco.status IN $statuses_sql
            GROUP BY pid
        ",
            $user_id
        );

        return $wpdb->get_results($sql);
    }

}