<?php

declare(strict_types=1);

namespace ACA\WC;

use AC;
use AC\Asset\Location\Absolute;
use AC\DI\Container;
use AC\Service\View;
use AC\Services;
use ACA\WC\Admin\TableIdsFactory;
use ACA\WC\Filtering\DefaultFilters;
use ACA\WC\Search\Query\OrderQueryController;
use ACA\WC\Service\PostTypes;
use ACA\WC\TableScreen\OrderFactory;
use ACA\WC\Value\ExtendedValue;
use ACP;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;
use ACP\Service\IntegrationStatus;
use ACP\Service\Storage\TemplateFiles;

final class WooCommerce implements Addon
{

    private Absolute $location;

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->location = $container->get(AdminColumnsPro::class)->get_addon_location($this->get_id());
    }

    public function get_id(): string
    {
        return 'woocommerce';
    }

    public function register(): void
    {
        if ( ! AC\WooCommerce::is_active()) {
            return;
        }

        $this->define_factories();
        $this->create_services()
             ->register();
    }

    private function define_factories(): void
    {
        $features = $this->container->get(Features::class);

        AC\TableIdsFactory\Aggregate::add(new TableIdsFactory());

        ACP\QuickAdd\Model\Factory::add_factory(new QuickAdd\Factory());

        ACP\Filtering\DefaultFilters\Aggregate::add(new DefaultFilters\Product());
        ACP\Filtering\DefaultFilters\Aggregate::add(new DefaultFilters\ProductVariation());
        ACP\Filtering\DefaultFilters\Aggregate::add(new DefaultFilters\Order());

        if ($features->use_hpos()) {
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

        ACP\Editing\BulkDelete\AggregateFactory::add($this->container->make(BulkDelete\Deletable\OrderFactory::class));

        $use = [
            'use_hpos'      => $features->use_hpos(),
            'use_analytics' => $features->use_analytics(),
        ];

        $column_factories = [
            ColumnFactories\ProductFactory::class                  => $use,
            ColumnFactories\Original\ProductFactory::class         => [],
            ColumnFactories\Original\ProductCategoryFactory::class => [],
            ColumnFactories\ProductVariationFactory::class         => $use,
            ColumnFactories\Original\ShopCouponFactory::class      => [],
            ColumnFactories\ShopCouponFactory::class               => [],
            ColumnFactories\UserFactory::class                     => $use,
            ColumnFactories\ShopOrderFactory::class                => [],
            ColumnFactories\Original\ShopOrderFactory::class       => [],
        ];

        foreach ($column_factories as $class => $params) {
            AC\ColumnFactories\Aggregate::add($this->container->make($class, $params));
        }

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\MediaFactory::class));

        $extended_values = [
            ExtendedValue\Product\Customers::class,
            ExtendedValue\Product\Variations::class,
            ExtendedValue\Product\GroupedProducts::class,
            ExtendedValue\Order\Notes::class,
            ExtendedValue\Order\Products::class,
            ExtendedValue\ShopCoupon\UsedBy::class,
            ExtendedValue\ShopCoupon\Orders::class,
            ExtendedValue\User\Products::class,
            ExtendedValue\User\Subscriptions::class,
            ExtendedValue\User\Orders::class,
            ExtendedValue\Media\PostsContainingImageInGallery::class,
        ];

        foreach ($extended_values as $extended_value) {
            AC\Value\ExtendedValueRegistry::add($this->container->make($extended_value));
        }

        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeading\OrderFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeading\OrderFactory::class));
        AC\Service\ManageValue::add(
            $this->container->make(
                RenderableServiceFactory::class,
                [
                    'factory' => $this->container->get(ListTable\ManageValue\OrderServiceFactory::class),
                ]
            )
        );
    }

    private function create_services(): Services
    {
        $features = $this->container->get(Features::class);

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

        if (apply_filters('ac/wc/show_product_variations', true)) {
            $services_fqn[] = PostType\ProductVariation::class;
            $services_fqn[] = Service\TableScreenProductVariations::class;
        }

        if ($features->use_hpos()) {
            $services_fqn[] = PostTypes::class;
        }

        if (class_exists('WC_Subscriptions', false)) {
            $services_fqn[] = Subscriptions\Subscriptions::class;
        }

        foreach ($services_fqn as $service) {
            $services->add($this->container->get($service));
        }

        $services->add(new AC\RequestAjaxParser($request_ajax_handlers));

        return $services;
    }

}