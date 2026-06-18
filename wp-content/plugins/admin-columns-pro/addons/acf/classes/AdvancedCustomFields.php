<?php

declare(strict_types=1);

namespace ACA\ACF;

use AC;
use AC\DI\Container;
use AC\Plugin\Version;
use AC\Services;
use ACP\Addon;
use ACP\Service\IntegrationStatus;

final class AdvancedCustomFields implements Addon
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get_id(): string
    {
        return 'acf';
    }

    private function get_acf_version(): Version
    {
        return new Version(acf()->version);
    }

    public function register(): void
    {
        if ( ! AC\Acf::is_active()) {
            return;
        }

        if ($this->get_acf_version()->is_lt(new Version('5.7'))) {
            return;
        }

        $this->define_factories();
        $this
            ->create_services()
            ->register();
    }

    private function define_factories(): void
    {
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\FieldsFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\OrderFieldsFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\MediaFactory::class));

        AC\Value\ExtendedValueRegistry::add(
            $this->container->get(Value\ExtendedValue\Media\PostsContainingImageInAcf::class)
        );
    }

    private function create_services(): Services
    {
        $request_ajax_handlers = new \AC\RequestAjaxHandlers();
        $request_ajax_handlers->add(
            'acp-acf-add-column',
            $this->container->get(RequestHandler\FieldSettingsAddColumn::class)
        );

        $services = new Services([
            new IntegrationStatus('ac-addon-acf'),
            new \AC\RequestAjaxParser($request_ajax_handlers),
            new \AC\Service\View($this->container->get('addon.acf.location')),
        ]);

        $class_names = [
            Service\ColumnGroup::class,
            Service\EditingFix::class,
            Service\FieldSettings::class,
            Service\Scripts::class,
        ];

        foreach ($class_names as $service) {
            $services->add($this->container->get($service));
        }

        return $services;
    }

}