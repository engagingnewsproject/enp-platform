<?php

declare(strict_types=1);

namespace ACA\YoastSeo;

use AC;
use AC\Registerable;
use AC\Services;
use AC\Vendor\DI;
use ACA\YoastSeo\Filtering\ActiveFilters;
use ACA\YoastSeo\Settings\ListScreen\TableElement\FilterReadabilityScore;
use ACA\YoastSeo\Settings\ListScreen\TableElement\FilterSeoScores;
use ACP;
use ACP\Service\IntegrationStatus;

class YoastSeo implements Registerable
{

    private $location;

    private $container;

    public function __construct(AC\Asset\Location\Absolute $location, DI\Container $container)
    {
        $this->location = $location;
        $this->container = $container;
    }

    public function register(): void
    {
        if ( ! defined('WPSEO_VERSION')) {
            return;
        }

        ACP\Filtering\DefaultFilters\Aggregate::add($this->container->get(ActiveFilters::class));

        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\PostFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\Original\TaxonomyFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\PostFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\TaxonomyFactory::class));
        AC\ColumnFactories\Aggregate::add($this->container->get(ColumnFactories\UserFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\ColumnGroups($this->location),
            new Service\HideFilters(new FilterSeoScores(), new FilterReadabilityScore()),
            new Service\Table(),
            new IntegrationStatus('ac-addon-yoast-seo'),
        ]);
    }

}