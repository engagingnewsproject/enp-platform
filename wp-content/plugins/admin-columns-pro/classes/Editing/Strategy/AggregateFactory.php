<?php

declare(strict_types=1);

namespace ACP\Editing\Strategy;

use AC\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class AggregateFactory implements StrategyFactory
{

    private static array $factories = [];

    public static function add(StrategyFactory $factory): void
    {
        array_unshift(self::$factories, $factory);
    }

    public function create(TableScreen $table_screen): ?Strategy
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