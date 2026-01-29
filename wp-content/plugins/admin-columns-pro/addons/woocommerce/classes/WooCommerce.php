<?php

declare(strict_types=1);

namespace ACA\WC;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACA\WC\Admin\MenuGroupFactory;
use ACA\WC\Admin\TableIdsFactory;
use ACA\WC\Filtering\DefaultFilters;
use ACA\WC\ListTable\ProductVariation;
use ACA\WC\Search\Query\OrderQueryController;
use ACA\WC\Service\ColumnGroups;
use ACA\WC\Service\PostTypes;
use ACA\WC\TableScreen\OrderFactory;
use ACA\WC\Value\ExtendedValue;
use ACP;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;
use ACP\Service\IntegrationStatus;
use ACP\Service\Storage\TemplateFiles;
use ACP\Service\View;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use WC_Subscriptions;

use function AC\Vendor\DI\autowire;

final class WooCommerce implements Registerable
{

    private Absolute $location;

    private DI\Container $container;

    public function __construct(Absolute $location, DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    public function register(): void
    {
        if ( ! class_exists('WooCommerce', false)) {
            return;
        }

        $this->define_container();
        $this->define_factories();

        $this->create_services()
             ->register();
    }

    private function define_container(): void
    {
        $location = new Absolute(
            $this->location->get_url(),
            $this->location->get_path()
        );

        $this->container->set(
            'use.hpos',
            static function (Features $features): bool {
                return $features->use_hpos();
            }
        );
        $this->container->set(
            'use.analytics',
            static function (Features $features): bool {
                return $features->use_analytics();
            }
        );
        $this->container->set(
            Admin::class,
            autowire()->constructorParameter(0, $location)
        );
        $this->container->set(
            PostType\ProductVariation::class,
            autowire()->constructorParameter(0, $location)
        );
        $this->container->set(
            ColumnGroups::class,
            autowire()->constructorParameter(0, $location)
        );
        $this->container->set(
            ProductVariation::class,
            autowire()->constructorParameter(0, $location)
        );
        $this->container->set(
            Service\TableScreen::class,
            autowire()->constructorParameter(1, $location)
                      ->constructorParameter(2, $this->use_product_variations())
        );
        $this->container->set(
            Features::class,
            autowire()->constructorParameter(
                0,
                wc_get_container()->has(FeaturesController::class)
                    ? wc_get_container()->get(FeaturesController::class)
                    : null
            )
        );
        $this->container->set(
            Subscriptions\Subscriptions::class,
            autowire()->constructorParameter(0, DI\get('use.hpos'))
                      ->constructorParameter(1, DI\get(DI\Container::class))
        );
    }

    private function define_factories(): void
    {
        ACP\QuickAdd\Model\Factory::add_factory(new QuickAdd\Factory());
        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());
        AC\TableIdsFactory\Aggregate::add(new TableIdsFactory());

        ACP\Filtering\DefaultFilters\Aggregate::add(new DefaultFilters\Product());
        ACP\Filtering\DefaultFilters\Aggregate::add(new DefaultFilters\ProductVariation());
        ACP\Filtering\DefaultFilters\Aggregate::add(new DefaultFilters\Order());

        if ($this->container->get('use.hpos')) {
            AC\TableScreenFactory\Aggregate::add($this->container->get(OrderFactory::class));
            ACP\Query\QueryRegistry::add($this->container->get(Search\Query\OrderFactory::class));
            AC\TableScreen\TableRowsFactory\Aggregate::add(new TableScreen\TableRowsFactory());

            AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\OrderFactory::class));
            AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\OrderFactory::class));
        }

        ACP\Search\TableMarkupFactory::register(TableScreen\Order::class, Search\TableScreen\Order::class);
        ACP\Filtering\TableScreenFactory::register(TableScreen\Order::class, Filtering\Table\Order::class);
        ACP\Export\Strategy\AggregateFactory::add($this->container->get(Export\Strategy\OrderFactory::class));
        ACP\Editing\Strategy\AggregateFactory::add($this->container->make(Editing\Strategy\OrderFactory::class));
        ACP\Editing\Strategy\AggregateFactory::add($this->container->make(Editing\Strategy\ProductFactory::class));

        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\ProductFactory::class, [
            'use_hpos'      => $this->container->get('use.hpos'),
            'use_analytics' => $this->container->get('use.analytics'),
        ]));
        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\Original\ProductFactory::class));
        AC\ColumnFactories\Aggregate::add(
            $this->container->make(ColumnFactories\Original\ProductCategoryFactory::class)
        );
        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\ProductVariationFactory::class, [
            'use_hpos'      => $this->container->get('use.hpos'),
            'use_analytics' => $this->container->get('use.analytics'),
        ]));

        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\Original\ShopCouponFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\ShopCouponFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\UserFactory::class, [
            'use_hpos'      => $this->container->get('use.hpos'),
            'use_analytics' => $this->container->get('use.analytics'),
        ]));
        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\ShopOrderFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->make(ColumnFactories\Original\ShopOrderFactory::class));

        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\Product\Customers::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\Product\Variations::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\Product\GroupedProducts::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\Order\Notes::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\Order\Products::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\ShopCoupon\UsedBy::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\ShopCoupon\Orders::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\User\Products::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\User\Subscriptions::class));
        AC\Value\ExtendedValueRegistry::add($this->container->make(ExtendedValue\User\Orders::class));

        ACP\Editing\BulkDelete\AggregateFactory::add($this->container->make(BulkDelete\Deletable\OrderFactory::class));

        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeading\OrderFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeading\OrderFactory::class));
        AC\Service\ManageValue::add(
            $this->container->make(
                RenderableServiceFactory::class,
                ['factory' => $this->container->get(ListTable\ManageValue\OrderServiceFactory::class)]
            )
        );
    }

    private function create_services(): Services
    {
        $request_ajax_handlers = new AC\RequestAjaxHandlers();
        $request_ajax_handlers->add(
            'ac-wc-order-meta-fields',
            $this->container->get(RequestHandler\Ajax\OrderMetaFields::class)
        );

        $services = new Services([
            new IntegrationStatus('ac-addon-woocommerce'),
            TemplateFiles::from_directory(__DIR__ . '/../config/storage/template'),
            new View($this->location),
        ]);

        $services_fqn = [
            Admin::class,
            Rounding::class,
            Service\Compatibility::class,
            Service\Editing::class,
            Service\QuickAdd::class,
            Service\Table::class,
            Service\ColumnGroups::class,
            Service\TableScreen::class,
            OrderQueryController::class,
        ];

        if ($this->use_product_variations()) {
            $services_fqn[] = PostType\ProductVariation::class;
        }

        if ($this->container->get('use.hpos')) {
            $services_fqn[] = PostTypes::class;
        }

        if ($this->use_subscriptions()) {
            $services_fqn[] = Subscriptions\Subscriptions::class;
        }

        foreach ($services_fqn as $service) {
            $services->add($this->container->get($service));
        }

        $services->add(new AC\RequestAjaxParser($request_ajax_handlers));

        return $services;
    }

    private function use_subscriptions(): bool
    {
        return class_exists('WC_Subscriptions', false) && version_compare(WC_Subscriptions::$version, '2.6', '>=');
    }

    private function use_product_variations(): bool
    {
        return apply_filters('ac/wc/show_product_variations', true) && version_compare(WC()->version, '3.3', '>=');
    }

}