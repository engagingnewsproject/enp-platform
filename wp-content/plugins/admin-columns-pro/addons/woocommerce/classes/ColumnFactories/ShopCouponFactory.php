<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\TableId;
use ACA\WC\ColumnFactory;

class ShopCouponFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen->get_id()->equals(new TableId('shop_coupon'))) {
            return $collection;
        }

        $factories = [
            ColumnFactory\ShopCoupon\AmountMaximum::class,
            ColumnFactory\ShopCoupon\AmountMinimum::class,
            ColumnFactory\ShopCoupon\Description::class,
            ColumnFactory\ShopCoupon\EmailRestrictions::class,
            ColumnFactory\ShopCoupon\ExcludedProducts::class,
            ColumnFactory\ShopCoupon\ExcludedProductsCategories::class,
            ColumnFactory\ShopCoupon\FreeShipping::class,
            ColumnFactory\ShopCoupon\IncludedProducts::class,
            ColumnFactory\ShopCoupon\Orders::class,
            ColumnFactory\ShopCoupon\Limit::class,
            ColumnFactory\ShopCoupon\ProductsCategories::class,
            ColumnFactory\ShopCoupon\UsedBy::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}