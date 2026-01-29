<?php

declare(strict_types=1);

namespace ACA\WC\Value\ExtendedValue\ShopCoupon;

use AC;
use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use WC_Coupon;
use WP_User;

class UsedBy implements ExtendedValue
{

    private const NAME = 'coupons-used-by';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $coupon = new WC_Coupon($id);
        $users = $coupon->get_used_by();

        if ( ! $users) {
            return '';
        }

        $values = [];

        foreach ($users as $user) {
            if (is_numeric($user)) {
                $user = get_userdata($user);

                if ($user instanceof WP_User) {
                    $label = ac_helper()->user->get_formatted_name($user);

                    $edit = get_edit_user_link($user->ID);

                    if ($edit) {
                        $label = ac_helper()->html->link($edit, $label);
                    }

                    $values[] = $label;
                }
            } elseif (is_email($user)) {
                $values[] = ac_helper()->html->link(
                    'mailto:' . $user,
                    $user,
                    ['tooltip' => __('Not a registered user', 'codepress-admin-columns')]
                );
            }
        }

        $view = new AC\View([
            'users' => $values,
        ]);

        return $view->set_template('modal-value/coupon-customers')->render();
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return (new ExtendedValueLink($label, $id, self::NAME))
            ->with_class('-nopadding -w-large');
    }

}