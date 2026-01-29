<?php

declare(strict_types=1);

namespace ACA\EC;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use AC\Vendor\DI\DependencyException;
use AC\Vendor\DI\NotFoundException;
use ACA\EC\Export\Strategy\EventFactory;
use ACA\EC\TableScreen\MenuGroupFactory;
use ACA\EC\Value\ExtendedValue;
use ACP;
use ACP\Export\Strategy\AggregateFactory;
use ACP\Service\IntegrationStatus;
use ACP\Service\Storage\TemplateFiles;

final class EventsCalendar implements Registerable
{

    private Absolute $location;

    private DI\Container $container;

    public function __construct(Absolute $location, DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function register(): void
    {
        if ( ! class_exists('Tribe__Events__Main', false)) {
            return;
        }

        $this->create_services()->register();

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\EventOriginalFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\EventSeriesFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\AdditionalFieldsFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\EventFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\OrganizerFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\VenueFactory::class));

        if (API::is_pro()) {
            AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\SeriesFactory::class));
        }

        AC\Value\ExtendedValueRegistry::add($this->container->get(ExtendedValue\VenueEvents::class));
        AC\Value\ExtendedValueRegistry::add($this->container->get(ExtendedValue\OrganizerEvents::class));
        AC\Value\ExtendedValueRegistry::add($this->container->get(ExtendedValue\EventSeries::class));

        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());

        // Export
        AggregateFactory::add($this->container->get(EventFactory::class));
    }

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    private function create_services(): Services
    {
        return new Services([
            new ACP\Service\View($this->location),
            new Service\ColumnGroups($this->location),
            new Service\Scripts($this->location),
            new Service\TableScreen($this->location, $this->container->get(ACP\Sorting\ModelFactory::class)),
            TemplateFiles::from_directory(__DIR__ . '/../config/storage/template'),
            new IntegrationStatus('ac-addon-events-calendar'),
        ]);
    }

}