<?php

declare(strict_types=1);

namespace ACA\WC\Export\Strategy;

use AC;
use ACA\WC\Export;
use ACA\WC\TableScreen;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class OrderFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof TableScreen\Order) {
            return null;
        }

        return new Export\Strategy\Order(
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}