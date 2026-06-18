<?php

declare(strict_types=1);

namespace ACA\EC;

use AC;
use AC\Asset\Location\Absolute;
use AC\DI\Container;
use AC\Services;
use ACA\EC\Editing\Strategy\EventStrategy;
use ACA\EC\Export\Strategy\EventFactory;
use ACA\EC\Value\ExtendedValue;
use ACP;
use ACP\Addon;
use ACP\AdminColumnsPro;
use ACP\Export\Strategy\AggregateFactory;
use ACP\Service\IntegrationStatus;
use ACP\Service\Storage\TemplateFiles;

final class EventsCalendar implements Addon
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
        return 'events-calendar';
    }

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

        ACP\Editing\Strategy\AggregateFactory::add($this->container->get(EventStrategy::class));

        // Export
        AggregateFactory::add($this->container->get(EventFactory::class));
    }

    private function create_services(): Services
    {
        return new Services([
            new AC\Service\View($this->location),
            new Service\ColumnGroups($this->location),
            new Service\Scripts($this->location),
            new Service\TableScreen($this->location, $this->container->get(ACP\Sorting\ModelFactory::class)),
            TemplateFiles::from_directory(__DIR__ . '/../config/storage/template'),
            new IntegrationStatus('ac-addon-events-calendar'),
        ]);
    }

}