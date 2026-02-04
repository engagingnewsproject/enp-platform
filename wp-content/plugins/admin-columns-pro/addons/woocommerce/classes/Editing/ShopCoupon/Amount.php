<?php

declare(strict_types=1);

namespace ACA\WC\Editing\ShopCoupon;

use ACP;
use ACP\Editing\View;
use WC_Coupon;

class Amount implements ACP\Editing\Service
{

    public function get_view(string $context): ?View
    {
        $view = new ACP\Editing\View\Number();
        $view->set_step('any');
        $view->set_min(0);

        return $view;
    }

    public function get_value(int $id): string
    {
        return (string)(new WC_Coupon($id))->get_amount();
    }

    public function update(int $id, $data): void
    {
        $coupon = new WC_Coupon($id);
        $coupon->set_amount($data);
        $coupon->save();
    }

}