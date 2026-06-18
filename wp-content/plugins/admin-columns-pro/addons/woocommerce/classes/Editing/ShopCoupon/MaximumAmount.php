<?php

declare(strict_types=1);

namespace ACA\WC\Editing\ShopCoupon;

use ACP;
use ACP\Editing\View;
use WC_Coupon;

class MaximumAmount implements ACP\Editing\Service
{

    public function get_view(string $context): ?View
    {
        $view = new ACP\Editing\View\Number();
        $view->set_step('any');
        $view->set_min(0);

        return $view;
    }

    public function get_value(int $id): float
    {
        // Force float because docblock of WC is wrong
        return (float)(new WC_Coupon($id))->get_maximum_amount();
    }

    public function update(int $id, $data): void
    {
        $coupon = new WC_Coupon($id);
        $coupon->set_maximum_amount($data);
        $coupon->save();
    }

}