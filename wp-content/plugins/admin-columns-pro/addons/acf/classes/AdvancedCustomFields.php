<?php

declare(strict_types=1);

namespace ACA\ACF;

use AC;
use AC\Asset\Location\Absolute;
use AC\Plugin\Version;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACA\ACF;
use ACP\Service\IntegrationStatus;

use function AC\Vendor\DI\autowire;

final class AdvancedCustomFields implements Registerable
{

    private Absolute $location;

    private DI\Container $container;

    public function __construct(Absolute $location, DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    private function get_acf_version(): Version
    {
        return new Version(acf()->version);
    }

    public function register(): void
    {
        if ( ! class_exists('acf', false)) {
            return;
        }

        if ($this->get_acf_version()->is_lt(new Version('5.7'))) {
            return;
        }

        $this->define_container();
        $this->define_factories();
        $this->create_services()
             ->register();
    }

    private function define_factories(): void
    {
        AC\ColumnFactories\Aggregate::add($this->container->get(ACF\ColumnFactories\FieldsFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ACF\ColumnFactories\OrderFieldsFactory::class));

        $location = new Absolute(
            $this->location->get_url(),
            $this->location->get_path()
        );

        $this->container->set(
            Service\ColumnGroup::class,
            autowire()->constructorParameter(0, $location)
        );
    }

    private function define_container(): void
    {
        $this->container->set(
            Service\Scripts::class,
            autowire()->constructorParameter(0, $this->location)
        );
    }

    private function create_services(): Services
    {
        $services = new Services([
            new IntegrationStatus('ac-addon-acf'),
        ]);

        $class_names = [
            Service\ColumnGroup::class,
            Service\EditingFix::class,
            Service\Scripts::class,
            Service\DateSaveFormat::class,
        ];

        foreach ($class_names as $service) {
            $services->add($this->container->get($service));
        }

        return $services;
    }

}