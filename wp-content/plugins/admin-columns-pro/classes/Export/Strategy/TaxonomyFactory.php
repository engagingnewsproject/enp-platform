<?php

declare(strict_types=1);

namespace ACP\Export\Strategy;

use AC\TableScreen;
use ACP;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class TaxonomyFactory implements StrategyFactory
{

    private ACP\Export\ResponseFactory $response_factory;

    public function __construct(ACP\Export\ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy) {
            return null;
        }

        return new Taxonomy(
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}