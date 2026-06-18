<?php

declare(strict_types=1);

namespace ACA\GravityForms;

use AC;
use AC\DI\Container;
use AC\Plugin\Version;
use AC\Services;
use ACA\GravityForms\Service\ColumnGroup;
use ACA\GravityForms\Service\Scripts;
use ACA\GravityForms\TableScreen\EntryFactory;
use ACA\GravityForms\TableScreen\TableIdsFactory;
use ACA\GravityForms\TableScreen\TableRowsFactory;
use ACP;
use ACP\Addon;
use ACP\Service\IntegrationStatus;
use GFForms;

final class GravityForms implements Addon
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get_id(): string
    {
        return 'gravityforms';
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

        $this->define_factories();

        $this->create_services()
             ->register();
    }

    private function define_factories(): void
    {
        AC\TableScreenFactory\Aggregate::add($this->container->get(EntryFactory::class));
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