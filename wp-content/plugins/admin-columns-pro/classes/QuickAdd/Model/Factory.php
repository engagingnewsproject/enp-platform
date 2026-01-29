<?php

namespace ACP\QuickAdd\Model;

use AC\TableScreen;

class Factory
{

    /**
     * @var ModelFactory[]
     */
    private static $factories = [];

    public static function add_factory(ModelFactory $factory)
    {
        self::$factories[] = $factory;
    }

    public static function create(TableScreen $table_screen): ?Create
    {
        foreach (array_reverse(self::$factories) as $factory) {
            $model = $factory->create($table_screen);

            if ($model) {
                return $model;
            }
        }

        return null;
    }

}