<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\WC\ColumnFactory;

class ProductFactory extends AC\ColumnFactories\BaseFactory
{

    private $use_hpos;

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

        if ( ! $table_screen instanceof AC\TableScreen\Post || ! $table_screen->get_post_type()->equals('product')) {
            return $collection;
        }

        $factories = [
            //Custom
            ColumnFactory\Product\AttributesFactory::class,
            ColumnFactory\Product\AvgOrderInterfalFactory::class,
            ColumnFactory\Product\BackordersAllowedFactory::class,
            ColumnFactory\Product\CouponsFactory::class,
            ColumnFactory\Product\CrossSellsFactory::class,
            ColumnFactory\Product\DefaultFormValuesFactory::class,
            ColumnFactory\Product\DimensionsFactory::class,
            ColumnFactory\Product\DownloadsFactory::class,
            ColumnFactory\Product\GalleryFactory::class,
            ColumnFactory\Product\GroupedProductsFactory::class,
            ColumnFactory\Product\GroupedByFactory::class,
            ColumnFactory\Product\GlobalUniqueId::class,
            ColumnFactory\Product\LowOnStockFactory::class,
            ColumnFactory\Product\MenuOrder::class,
            ColumnFactory\Product\ProductTypeFactory::class,
            ColumnFactory\Product\PurchaseNoteFactory::class,
            ColumnFactory\Product\RatingFactory::class,
            ColumnFactory\Product\ReviewsFactory::class,
            ColumnFactory\Product\ReviewsEnabledFactory::class,
            ColumnFactory\Product\SaleFactory::class,
            ColumnFactory\Product\ShippingClassFactory::class,
            ColumnFactory\Product\ShortDescription::class,
            ColumnFactory\Product\SoldIndividuallyFactory::class,
            ColumnFactory\Product\StockAmountFactory::class,
            ColumnFactory\Product\StockStatusFactory::class,
            ColumnFactory\Product\StockThresholdFactory::class,
            ColumnFactory\Product\TaxClassFactory::class,
            ColumnFactory\Product\TaxStatusFactory::class,
            ColumnFactory\Product\UpsellsFactory::class,
            ColumnFactory\Product\VariationFactory::class,
            ColumnFactory\Product\VisibilityFactory::class,
            ColumnFactory\Product\WeightFactory::class,
        ];

        if ($this->use_analytics) {
            $factories[] = ColumnFactory\Product\AvgOrderInterfalFactory::class;
            $factories[] = ColumnFactory\Product\CustomersFactory::class;
            $factories[] = ColumnFactory\Product\LastPurchaseDate::class;
            $factories[] = ColumnFactory\Product\OrderCountFactory::class;
            $factories[] = ColumnFactory\Product\OrderTotalFactory::class;
            $factories[] = ColumnFactory\Product\SalesFactory::class;
        }

        // Overwrite HPOS columns with Legacy columns
        if ( ! $this->use_hpos) {
            $factories[] = ColumnFactory\Product\ShopOrder\AvgOrderInterfalFactory::class;
            $factories[] = ColumnFactory\Product\ShopOrder\CustomersFactory::class;
            $factories[] = ColumnFactory\Product\ShopOrder\OrderCountFactory::class;
            $factories[] = ColumnFactory\Product\ShopOrder\OrderTotalFactory::class;
            $factories[] = ColumnFactory\Product\ShopOrder\SalesFactory::class;
        }

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}