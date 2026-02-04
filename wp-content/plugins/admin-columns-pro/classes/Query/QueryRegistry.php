<?php

declare(strict_types=1);

namespace ACP\Query;

use AC\TableScreen;
use ACP\Query;
use LogicException;

class QueryRegistry
{

    /**
     * @var QueryFactory[] $factories
     */
    private static array $factories = [];

    public static function add(QueryFactory $factory): void
    {
        self::$factories[] = $factory;
    }

    public static function can_create(TableScreen $table_screen): bool
    {
        foreach (self::$factories as $factory) {
            if ($factory->can_create($table_screen)) {
                return true;
            }
        }

        return false;
    }

    public static function create(TableScreen $table_screen, array $bindings): Query
    {
        foreach (self::$factories as $factory) {
            if ($factory->can_create($table_screen)) {
                return $factory->create($bindings);
            }
        }

        throw new LogicException('No query factory found for table screen: ' . $table_screen->get_id());
    }

}