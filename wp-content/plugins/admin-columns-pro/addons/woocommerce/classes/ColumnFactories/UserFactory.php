<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\WC\ColumnFactory;

class UserFactory extends AC\ColumnFactories\BaseFactory
{

    private bool $use_hpos;

    private bool $use_analytics;

    public function __construct(Container $container, bool $use_hpos, bool $use_analytics)
    {
        parent::__construct($container);

        $this->use_hpos = $use_hpos;
        $this->use_analytics = $use_analytics;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\User) {
            return $collection;
        }

        $factories = [
            //Custom
            ColumnFactory\User\AddressFactory::class,
            ColumnFactory\User\CountryFactory::class,
            ColumnFactory\User\CustomerSinceFactory::class,
            ColumnFactory\User\FirstOrderFactory::class,
            ColumnFactory\User\LastOrderFactory::class,
            ColumnFactory\User\LastActive::class,
            ColumnFactory\User\OrderCountFactory::class,
            ColumnFactory\User\Orders::class,
            ColumnFactory\User\RatingFactory::class,
            ColumnFactory\User\ReviewsFactory::class,
            ColumnFactory\User\TotalSalesFactory::class,
            ColumnFactory\User\OrderEmailsFactory::class,
        ];

        if ($this->use_analytics) {
            $factories[] = ColumnFactory\User\ProductsFactory::class;
        }

        if ( ! $this->use_hpos) {
            $factories[] = ColumnFactory\User\ShopOrder\CouponUsed::class;
            $factories[] = ColumnFactory\User\ShopOrder\CustomerSince::class;
            $factories[] = ColumnFactory\User\ShopOrder\FirstOrder::class;
            $factories[] = ColumnFactory\User\ShopOrder\LastOrder::class;
            $factories[] = ColumnFactory\User\ShopOrder\OrderCount::class;
            $factories[] = ColumnFactory\User\ShopOrder\Orders::class;
            $factories[] = ColumnFactory\User\ShopOrder\Products::class;
            $factories[] = ColumnFactory\User\ShopOrder\TotalSales::class;
        }

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}