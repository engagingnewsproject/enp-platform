<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder;

use AC;
use AC\DI\Container;
use AC\Services;
use AC\Type\Labels;
use ACA\BeaverBuilder\TableScreen\TableIdsFactory;
use ACA\BeaverBuilder\TableScreen\TemplateFactory;
use ACP\Addon;
use ACP\ConditionalFormat\ManageValue\RenderableServiceFactory;

class BeaverBuilder implements Addon
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get_id(): string
    {
        return 'beaver-builder';
    }

    public function register(): void
    {
        if ( ! class_exists('FLBuilderLoader')) {
            return;
        }

        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'layout',
                new Labels(
                    __('Template', 'fl-builder'),
                    __('Templates', 'fl-builder')
                )
            )
        );
        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'row',
                new Labels(
                    __('Saved Row', 'fl-builder'),
                    __('Saved Rows', 'fl-builder')
                )
            )
        );
        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'column',
                new Labels(
                    __('Saved Column', 'fl-builder'),
                    __('Saved Columns', 'fl-builder')
                )
            )
        );
        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'module',
                new Labels(
                    __('Saved Module', 'fl-builder'),
                    __('Saved Modules', 'fl-builder')
                )
            )
        );
        AC\TableIdsFactory\Aggregate::add(new TableIdsFactory());
        AC\Service\ManageValue::add(
            $this->container->make(
                RenderableServiceFactory::class,
                ['factory' => $this->container->get(ListTable\ManageValue\TemplateFactory::class)]
            )
        );

        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeading\TemplateFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeading\TemplateFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\PostTypes(),
            new Service\ColumnRenderTaxonomyFilter(),
        ]);
    }

}