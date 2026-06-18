<?php

declare(strict_types=1);

namespace ACA\MLA\Export;

use AC;
use AC\TableScreen;
use ACP;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;

class StrategyFactory implements ACP\Export\StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(
        ResponseFactory $response_factory
    ) {
        $this->response_factory = $response_factory;
    }

    public function create(TableScreen $table_screen): ?ACP\Export\Strategy
    {
        if ( ! $table_screen instanceof AC\ThirdParty\MediaLibraryAssistant\TableScreen) {
            return null;
        }

        return new Strategy(
            $this->response_factory,
            new TableDataFactory($table_screen)
        );
    }

}