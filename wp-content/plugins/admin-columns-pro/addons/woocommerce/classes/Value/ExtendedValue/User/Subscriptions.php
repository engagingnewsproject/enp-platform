<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\User;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use WC_Order_Item_Product;
use WC_Subscription;

class Subscriptions implements ExtendedValue
{

    private const NAME = 'user-subscriptions';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $items = [];

        foreach (wcs_get_users_subscriptions($id) as $subscription) {
            $items[] = [
                'subscription' => ac_helper()->html->link(
                    get_edit_post_link($subscription->get_id()),
                    (string)$subscription->get_id()
                ),
                'status'       => wcs_get_subscription_status_name('wc-' . $subscription->get_status()),
                'product'      => $this->get_product($subscription),
                'total'        => wc_price($subscription->get_total()),
            ];
        }

        $view = new AC\View([
            'items' => $items,
        ]);

        return $view->set_template('modal-value/subscriptions')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-nopadding');
    }

    private function get_product(WC_Subscription $subscription): ?string
    {
        foreach ($subscription->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product) {
                $id = $item->get_product()->get_id();
                $label = $item->get_product()->get_title() ?: $id;

                $link = get_edit_post_link($id);
                if ($link) {
                    return ac_helper()->html->link($link, $label);
                }

                return (string)$label;
            }
        }

        return null;
    }

}