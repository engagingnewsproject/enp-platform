<?php

declare(strict_types=1);

namespace ACP\TableScreen\RelatedRepository;

use AC\Table\TableScreenCollection;
use AC\Type\TableId;
use ACP\TableScreen\RelatedRepository;

class Aggregate implements RelatedRepository
{

    private static array $repositories = [];

    public static function add(RelatedRepository $repository): void
    {
        self::$repositories[] = $repository;
    }

    public function find_all(TableId $table_id): TableScreenCollection
    {
        $table_screens = new TableScreenCollection();

        foreach (self::$repositories as $repository) {
            foreach ($repository->find_all($table_id) as $screen) {
                $table_screens->add($screen);
            }
        }

        return $table_screens;
    }

}