<?php

declare(strict_types=1);

namespace ACP\Editing\BulkDelete;

use AC\TableScreen;

class AggregateFactory implements StrategyFactory
{

    private static array $factories = [];

    public static function add(StrategyFactory $factory): void
    {
        self::$factories[] = $factory;
    }

    public function create(TableScreen $table_screen): ?Deletable
    {
        foreach (self::$factories as $factory) {
            $strategy = $factory->create($table_screen);

            if ( ! $strategy) {
                continue;
            }

            return $strategy;
        }

        return null;
    }

}