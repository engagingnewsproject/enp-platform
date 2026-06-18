<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Export\Strategy;

use AC\TableScreen;
use ACA\WC;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class OrderSubscriptionFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof WC\Subscriptions\TableScreen\OrderSubscription) {
            return null;
        }

        return new WC\Export\Strategy\Order(
            new TableDataFactory($table_screen),
            $this->response_factory,
            'shop_subscription'
        );
    }

}