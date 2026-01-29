<?php

declare(strict_types=1);

namespace ACA\GravityForms;

use AC;
use AC\Asset\Location\Absolute;
use AC\Plugin\Version;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACA\GravityForms\Service\ColumnGroup;
use ACA\GravityForms\Service\Scripts;
use ACA\GravityForms\TableScreen\EntryFactory;
use ACA\GravityForms\TableScreen\MenuGroupFactory;
use ACA\GravityForms\TableScreen\TableIdsFactory;
use ACA\GravityForms\TableScreen\TableRowsFactory;
use ACP;
use ACP\Service\IntegrationStatus;
use GFForms;

use function AC\Vendor\DI\autowire;

final class GravityForms implements Registerable
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
        if ( ! class_exists('GFForms', false) || ! isset(GFForms::$version)) {
            return;
        }

        $version = new Version(GFForms::$version);

        if ( ! $version->is_valid() || $version->is_lt(new Version('2.5'))) {
            return;
        }

        $this->define_container();
        $this->define_factories();

        $this->create_services()
             ->register();
    }

    private function define_container(): void
    {
        $this->container->set(
            Scripts::class,
            autowire()->constructorParameter(0, $this->location)
        );
    }

    private function define_factories(): void
    {
        AC\TableScreenFactory\Aggregate::add($this->container->get(EntryFactory::class));
        AC\Admin\MenuGroupFactory\Aggregate::add($this->container->get(MenuGroupFactory::class));
        AC\TableIdsFactory\Aggregate::add($this->container->get(TableIdsFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\EntryFactory::class));
        AC\TableScreen\TableRowsFactory\Aggregate::add($this->container->get(TableRowsFactory::class));
        ACP\Export\Strategy\AggregateFactory::add($this->container->get(Export\Strategy\EntryFactory::class));
        ACP\Editing\Strategy\AggregateFactory::add($this->container->make(Editing\Strategy\EntryFactory::class));
        ACP\Query\QueryRegistry::add($this->container->get(Query\EntryFactory::class));
        ACP\Search\TableMarkupFactory::register(TableScreen\Entry::class, Search\TableScreen\Entry::class);
        ACP\Filtering\TableScreenFactory::register(TableScreen\Entry::class, Filtering\Table\Entry::class);
        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeading\EntryFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeading\EntryFactory::class));
    }

    private function create_services(): Services
    {
        $services = new Services([
            new IntegrationStatus('ac-addon-gravityforms'),
        ]);

        $class_names = [
            Service\Entry::class,
            Service\StoreOriginalColumns::class,
            Service\Admin::class,
            Scripts::class,
            ColumnGroup::class,
        ];

        foreach ($class_names as $class_name) {
            $services->add($this->container->get($class_name));
        }

        return $services;
    }

}