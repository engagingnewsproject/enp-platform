<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC\TableScreen;
use AC\Type\TableId;
use ACA\WC\ColumnFactory\ShopCoupon\Original;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class ShopCouponFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen->get_id()->equals(new TableId('shop_coupon'))) {
            return [];
        }

        return [
            'amount'      => Original\AmountFactory::class,
            'coupon'      => Original\CouponFactory::class,
            'coupon_code' => Original\CouponCodeFactory::class,
            'description' => Original\DescriptionFactory::class,
            'expiry_date' => Original\ExpiryDate::class,
            'products'    => Original\Products::class,
            'type'        => Original\Type::class,
            'usage'       => Original\Usage::class,
        ];
    }

}