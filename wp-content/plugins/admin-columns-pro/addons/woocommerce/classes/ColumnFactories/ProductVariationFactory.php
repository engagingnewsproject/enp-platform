<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\WC\ColumnFactory;

class ProductVariationFactory extends AC\ColumnFactories\BaseFactory
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

        if ( ! $table_screen instanceof AC\TableScreen\Post ||
             ! $table_screen->get_post_type()->equals('product_variation')) {
            return $collection;
        }

        $factories = [
            //Custom
            ColumnFactory\ProductVariation\AttributeFactory::class,
            ColumnFactory\ProductVariation\DescriptionFactory::class,
            ColumnFactory\ProductVariation\DownloadableFactory::class,
            ColumnFactory\ProductVariation\EnabledFactory::class,
            ColumnFactory\ProductVariation\GlobalUniqueId::class,
            ColumnFactory\ProductVariation\ParentProductFactory::class,
            ColumnFactory\ProductVariation\PriceFactory::class,
            ColumnFactory\ProductVariation\ShippingClassFactory::class,
            ColumnFactory\ProductVariation\SkuFactory::class,
            ColumnFactory\ProductVariation\StockFactory::class,
            ColumnFactory\ProductVariation\TaxClassFactory::class,
            ColumnFactory\ProductVariation\ProductTaxonomy::class,
            ColumnFactory\ProductVariation\ProductImageFactory::class,
            ColumnFactory\ProductVariation\VariationFactory::class,
            ColumnFactory\ProductVariation\VirtualFactory::class,
            ColumnFactory\ProductVariation\WeightFactory::class,

            // Product Columns
            ColumnFactory\Product\LowOnStockFactory::class,
            ColumnFactory\Product\DimensionsFactory::class,
        ];

        if ($this->use_hpos && $this->use_analytics) {
            $factories[] = ColumnFactory\Product\SalesFactory::class;
        }

        if ( ! $this->use_hpos) {
            $factories[] = ColumnFactory\Product\ShopOrder\SalesFactory::class;
        }

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}